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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArticleService
{
    /**
     * Skor SEO minimal agar artikel boleh diantrekan / dipublikasikan.
     */
    private const MIN_PUBLISH_SCORE = 80;

    private const IMAGE_DISK = 'public';

    private const IMAGE_DIR = 'articles/featured_images';

    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
    ) {
    }

    /**
     * Simpan artikel baru beserta relasi (kategori, tag, situs) & metadata SEO.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?UploadedFile $image = null): Article
    {
        return DB::transaction(function () use ($data, $image): Article {
            $analysis = $this->seoAnalyzer->analyze($data);

            $article = Article::create([
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id'],
                // Kolom legacy tunggal tetap diisi sebagai mirror (kompat data lama).
                'category_id' => $data['categories'][0] ?? null,
                'wp_site_id' => $data['wp_site_ids'][0] ?? null,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'content' => $data['content'],
                'featured_image_path' => $image ? $this->storeFeaturedImage($image) : null,
                'image_alt_text' => $data['image_alt_text'] ?? null,
                'seo_score' => $analysis['score'],
                'yoast_title' => $data['yoast_title'] ?? $data['title'],
                'yoast_metadesc' => $data['yoast_metadesc'] ?? null,
                'yoast_focuskw' => $data['yoast_focuskw'] ?? null,
                'status' => 'draft',
            ]);

            $this->syncRelations($article, $data);
            $this->upsertSeoMeta($article, $data, $analysis);

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
            $analysis = $this->seoAnalyzer->analyze($data);

            $payload = [
                'category_id' => $data['categories'][0] ?? $article->category_id,
                'wp_site_id' => $data['wp_site_ids'][0] ?? $article->wp_site_id,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'content' => $data['content'],
                'image_alt_text' => $data['image_alt_text'] ?? null,
                'seo_score' => $analysis['score'],
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
     * Gerbang publikasi: hitung ulang skor SEO server-side sebelum mengantrekan job.
     */
    public function publish(Article $article): Article
    {
        $analysis = $this->seoAnalyzer->analyze($this->seoInput($article));

        if ($analysis['score'] < self::MIN_PUBLISH_SCORE) {
            throw ValidationException::withMessages([
                'seo_score' => 'Publish diblokir karena skor SEO harus minimal '.self::MIN_PUBLISH_SCORE.'.',
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
                'seo_score' => $analysis['score'],
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
     * Daftar artikel per company dengan eager loading (cegah N+1).
     */
    public function listForCompany(int $companyId): Collection
    {
        return Article::withoutGlobalScope(TenantScope::class)
            ->with(['seoMeta', 'categories', 'tags', 'sitePublications.wpSite', 'author'])
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
            ->with(['seoMeta', 'categories', 'tags', 'sitePublications.wpSite', 'author'])
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
            // Dukung ID numerik (tag lama) maupun nama (tag freeform baru).
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

        // Hapus situs yang tidak lagi dipilih.
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
                'seo_score' => $analysis['score'],
                'content_score' => $analysis['content_score'] ?? null,
                'reading_time_minutes' => $analysis['estimated_reading_time_minutes'] ?? null,
            ],
        );
    }

    private function storeFeaturedImage(UploadedFile $image): string
    {
        return $image->store(self::IMAGE_DIR, self::IMAGE_DISK);
    }

    private function deleteFeaturedImage(?string $path): void
    {
        if ($path && Storage::disk(self::IMAGE_DISK)->exists($path)) {
            Storage::disk(self::IMAGE_DISK)->delete($path);
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
        ];
    }
}
