<?php

namespace App\Services;

use App\Jobs\PublishArticleToWordPressJob;
use App\Models\Article;
use App\Models\ArticleSitePublication;
use App\Models\Scopes\TenantScope;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleService
{
    /**
     * Skor SEO & Readability minimal agar artikel boleh diantrekan / dipublikasikan.
     */
    private const MIN_PUBLISH_SCORE = 80;

    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
        private readonly ImageService $imageService,
        private readonly WordPressPublisherService $wpPublisher,
    ) {}

    /**
     * Simpan artikel baru beserta relasi (kategori, tag, situs) & metadata SEO.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $image = null): Article
    {
        return DB::transaction(function () use ($data, $image): Article {
            $start = microtime(true);

            $seoStart = microtime(true);
            $analysis = $this->seoAnalyzer->analyze($data);
            $seoElapsed = round((microtime(true) - $seoStart) * 1000, 2);

            $imageStart = microtime(true);
            $featuredPath = $image ? $this->storeFeaturedImage($image) : null;
            $imageElapsed = round((microtime(true) - $imageStart) * 1000, 2);

            $article = Article::create([
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                'category_id' => $data['categories'][0] ?? null,
                'wp_site_id' => $data['wp_site_ids'][0] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'content' => $data['content'],
                'featured_image_path' => $featuredPath,
                'image_alt_text' => $data['image_alt_text'] ?? null,
                'seo_score' => $analysis['seo_score'],
                'readability_score' => $analysis['readability_score'],
                'yoast_title' => $data['yoast_title'] ?? $data['title'],
                'yoast_metadesc' => $data['yoast_metadesc'] ?? null,
                'yoast_focuskw' => $data['yoast_focuskw'] ?? null,
                'status' => 'draft',
            ]);

            $this->syncRelations($article, $data);
            $this->upsertSeoMeta($article, $data, $analysis);

            Log::info('[ArticleService] store rincian', [
                'has_image' => $image !== null,
                'seo_ms' => $seoElapsed,
                'image_ms' => $imageElapsed,
                'total_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);

            return $article;
        });
    }

    /**
     * Perbarui artikel yang ada; ganti featured image bila diunggah ulang.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Article $article, array $data, ?UploadedFile $image = null): Article
    {
        return DB::transaction(function () use ($article, $data, $image): Article {
            $data['article_id'] = $article->id;
            $analysis = $this->seoAnalyzer->analyze($data);

            $payload = [
                'category_id' => $data['categories'][0] ?? $article->category_id,
                'wp_site_id' => $data['wp_site_ids'][0] ?? $article->wp_site_id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'content' => $data['content'],
                'image_alt_text' => $data['image_alt_text'] ?? null,
                'seo_score' => $analysis['seo_score'],
                'readability_score' => $analysis['readability_score'],
                'yoast_title' => $data['yoast_title'] ?? $data['title'],
                'yoast_metadesc' => $data['yoast_metadesc'] ?? null,
                'yoast_focuskw' => $data['yoast_focuskw'] ?? null,
            ];

            if ($image) {
                $this->deleteFeaturedImage($article->featured_image_path);
                $payload['featured_image_path'] = $this->storeFeaturedImage($image);
            }

            $article->update($payload);

            $this->syncRelations($article, $data);
            $this->upsertSeoMeta($article, $data, $analysis);

            return $article->refresh();
        });
    }

    /**
     * Gerbang publikasi: hitung ulang skor SEO + Readability server-side sebelum mengantrekan job.
     */
    public function publish(Article $article): Article
    {
        $start = microtime(true);
        $analysis = $this->seoAnalyzer->analyze($this->seoInput($article));

        if ($analysis['seo_score'] < self::MIN_PUBLISH_SCORE) {
            throw ValidationException::withMessages([
                'seo_score' => 'Publish diblokir: skor SEO harus minimal '.self::MIN_PUBLISH_SCORE.'. (skor saat ini: '.$analysis['seo_score'].')',
            ]);
        }

        if ($analysis['readability_score'] < self::MIN_PUBLISH_SCORE) {
            throw ValidationException::withMessages([
                'readability_score' => 'Publish diblokir: skor Readability harus minimal '.self::MIN_PUBLISH_SCORE.'. (skor saat ini: '.$analysis['readability_score'].')',
            ]);
        }

        $siteIds = $article->sitePublications()->pluck('wp_site_id');

        if ($siteIds->isEmpty()) {
            throw ValidationException::withMessages([
                'wp_site_ids' => 'Artikel belum memiliki situs WordPress target.',
            ]);
        }

        DB::transaction(function () use ($article, $analysis, $siteIds): void {
            $article->update([
                'seo_score' => $analysis['seo_score'],
                'readability_score' => $analysis['readability_score'],
                'status' => 'queued',
            ]);

            $article->sitePublications()
                ->whereIn('wp_site_id', $siteIds)
                ->update(['status' => 'queued']);
        });

        // Satu job per situs target agar kegagalan satu situs tidak menggagalkan yang lain.
        foreach ($siteIds as $siteId) {
            PublishArticleToWordPressJob::dispatch($article->id, (int) $siteId);
        }

        Log::info('[ArticleService] publish selesai', [
            'article_id' => $article->id,
            'site_count' => $siteIds->count(),
            'seo_score' => $analysis['seo_score'],
            'readability_score' => $analysis['readability_score'],
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $article->refresh();
    }

    /**
     * Kirim ulang publikasi. Bila $siteId diberikan, hanya situs itu; jika tidak,
     * seluruh situs yang berstatus failed.
     */
    public function retry(Article $article, ?int $siteId = null): Article
    {
        $query = $article->sitePublications();

        if ($siteId !== null) {
            $query->where('wp_site_id', $siteId);
        } else {
            $query->where('status', 'failed');
        }

        $siteIds = $query->pluck('wp_site_id');

        if ($siteIds->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => 'Tidak ada publikasi situs yang bisa di-retry.',
            ]);
        }

        $article->sitePublications()
            ->whereIn('wp_site_id', $siteIds)
            ->update(['status' => 'queued']);

        if ($article->status === 'failed') {
            $article->update(['status' => 'queued']);
        }

        foreach ($siteIds as $id) {
            PublishArticleToWordPressJob::dispatch($article->id, (int) $id);
        }

        return $article->refresh();
    }

    /**
     * Hapus artikel lintas platform: hapus post di SEMUA situs WordPress target yang
     * sudah terpublikasi (memiliki wp_post_id), lalu hapus record di database.
     *
     * Kegagalan menghapus post di satu situs tidak menghentikan penghapusan situs
     * lain / record lokal; hanya dicatat log warning.
     */
    public function delete(Article $article): void
    {
        $publications = $article->sitePublications()
            ->whereNotNull('wp_post_id')
            ->with('wpSite')
            ->get();

        foreach ($publications as $publication) {
            $site = $publication->wpSite;

            if (! $site || ! $publication->wp_post_id) {
                continue;
            }

            try {
                $this->wpPublisher->deleteFromWordPress($site, (int) $publication->wp_post_id);
            } catch (\Throwable $e) {
                Log::warning("Gagal hapus WP post #{$publication->wp_post_id} pada '{$site->site_name}': ".$e->getMessage());
            }
        }

        $this->deleteFeaturedImage($article->featured_image_path);

        $article->delete();
    }

    /**
     * Hapus publikasi artikel pada SATU situs WordPress (unpublish per-situs):
     * hapus post di WP, hapus baris mapping article_site_publications, lalu
     * hitung ulang status agregat artikel.
     */
    public function deleteSitePublication(Article $article, ArticleSitePublication $publication): void
    {
        $site = $publication->wpSite;

        if ($site && $publication->wp_post_id) {
            try {
                $this->wpPublisher->deleteFromWordPress($site, (int) $publication->wp_post_id);
            } catch (\Throwable $e) {
                Log::warning("Gagal hapus WP post #{$publication->wp_post_id} pada '{$site->site_name}': ".$e->getMessage());
            }
        }

        $publication->delete();

        $this->recomputeArticleStatus($article);
    }

    /**
     * Status artikel = agregat dari seluruh publikasi situs yang tersisa:
     * - semua published  => published
     * - ada yang failed   => failed
     * - selain itu        => queued (atau draft bila tidak ada publikasi)
     */
    private function recomputeArticleStatus(Article $article): void
    {
        $statuses = $article->sitePublications()->pluck('status');

        if ($statuses->isEmpty()) {
            $article->update(['status' => 'draft']);

            return;
        }

        $status = match (true) {
            $statuses->every(fn ($s) => $s === 'published') => 'published',
            $statuses->contains('failed') => 'failed',
            default => 'queued',
        };

        $article->update(['status' => $status]);
    }

    /**
     * Daftar artikel per company dengan eager loading (cegah N+1).
     */
    public function listForCompany(int $companyId): Collection
    {
        return Article::withoutGlobalScope(TenantScope::class)
            ->with([
                'seoMeta',
                'categories' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'tags' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'sitePublications.wpSite' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'author',
            ])
            ->forCompany($companyId)
            ->latest()
            ->get();
    }

    /**
     * Daftar artikel per company dengan paginasi + pencarian (untuk halaman index).
     * TenantScope dilepas: konteks artikel independen dari scope global (active_company_id),
     * isolasi tenant ditegakkan eksplisit lewat forCompany($companyId).
     */
    public function paginateForCompany(int $companyId, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Article::withoutGlobalScope(TenantScope::class)
            ->with([
                'seoMeta',
                'categories' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'tags' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'sitePublications.wpSite' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId),
                'author',
            ])
            ->forCompany($companyId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Sinkronkan relasi pivot kategori, tag, dan situs publikasi.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncRelations(Article $article, array $data): void
    {
        $article->categories()->sync($data['categories'] ?? []);
        $article->tags()->sync($this->resolveTagIds($article->company_id, $data['tags'] ?? []));
        $this->syncSitePublications($article, $data['wp_site_ids'] ?? []);
    }

    /**
     * Konversi daftar nama tag (freeform) menjadi array ID, meng-upsert tag baru
     * pada company terkait. Tenant aman: tag selalu terikat pada company artikel.
     *
     * @param  array<int, string|int>  $tags
     * @return array<int, int>
     */
    private function resolveTagIds(int $companyId, array $tags): array
    {
        $ids = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $ids[] = (int) $tag;

                continue;
            }

            $name = trim((string) $tag);

            if ($name === '') {
                continue;
            }

            $model = Tag::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['company_id' => $companyId, 'slug' => Str::slug($name)],
                ['name' => $name],
            );

            $ids[] = $model->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Upsert baris publikasi per situs tanpa menghapus riwayat wp_post_id yang sudah ada.
     *
     * @param  array<int, int>  $siteIds
     */
    private function syncSitePublications(Article $article, array $siteIds): void
    {
        $siteIds = array_values(array_unique(array_map('intval', $siteIds)));

        $article->sitePublications()
            ->whereNotIn('wp_site_id', $siteIds ?: [0])
            ->delete();

        foreach ($siteIds as $siteId) {
            ArticleSitePublication::firstOrCreate(
                ['article_id' => $article->id, 'wp_site_id' => $siteId],
                ['status' => 'draft'],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $analysis
     */
    private function upsertSeoMeta(Article $article, array $data, array $analysis): void
    {
        $article->seoMeta()->updateOrCreate(
            ['article_id' => $article->id],
            [
                'yoast_title' => $data['yoast_title'] ?? $data['title'] ?? null,
                'yoast_metadesc' => $data['yoast_metadesc'] ?? null,
                'yoast_focuskw' => $data['yoast_focuskw'] ?? null,
                'seo_score' => $analysis['seo_score'],
                'readability_score' => $analysis['readability_score'],
                'reading_time_minutes' => $analysis['estimated_reading_time_minutes'] ?? null,
            ],
        );
    }

    /**
     * Proses simpan featured image via ImageService (otomatis konversi WebP & resize).
     */
    private function storeFeaturedImage(UploadedFile $image): ?string
    {
        return $this->imageService->processUpload($image, 'articles');
    }

    /**
     * Hapus file featured image via ImageService.
     */
    private function deleteFeaturedImage(?string $path): void
    {
        if ($path) {
            $this->imageService->deleteFile($path);
        }
    }

    /**
     * Rakit input untuk SeoAnalyzer dari model + relasi SEO meta.
     *
     * @return array<string, mixed>
     */
    private function seoInput(Article $article): array
    {
        $seo = $article->seoMeta;

        return [
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'image_alt_text' => $article->image_alt_text,
            'yoast_title' => $seo?->yoast_title ?? $article->yoast_title,
            'yoast_metadesc' => $seo?->yoast_metadesc ?? $article->yoast_metadesc,
            'yoast_focuskw' => $seo?->yoast_focuskw ?? $article->yoast_focuskw,
            'article_id' => $article->id,
        ];
    }
}
