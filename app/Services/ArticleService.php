<?php

namespace App\Services;

use App\Jobs\PublishArticleToWordPressJob;
use App\Models\Article;
use App\Models\ArticleSitePublication;
use App\Models\Scopes\TenantScope;
use App\Models\Tag;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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

    /**
     * Jeda minimal (menit) antara sekarang dan waktu jadwal publish.
     * Memberi ruang bagi scheduler per-menit untuk mengklaim tanpa race.
     */
    private const MIN_LEAD_MINUTES = 5;

    /**
     * Horizon maksimal penjadwalan dalam hari (setahun).
     */
    private const MAX_HORIZON_DAYS = 365;

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
     * Gerbang publikasi: hitung ulang skor SEO + Readability server-side lalu
     * antrekan job ke seluruh situs target. Membersihkan jadwal aktif (bila ada)
     * karena artikel kini dipercepat publish segera.
     */
    public function publish(Article $article): Article
    {
        $start = microtime(true);

        $analysis = $this->assertPublishable($article);
        $this->queuePublication($article, $analysis);

        Log::info('[ArticleService] publish selesai', [
            'article_id' => $article->id,
            'seo_score' => $analysis['seo_score'],
            'readability_score' => $analysis['readability_score'],
            'total_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);

        return $article->refresh();
    }

    /**
     * Jadwalkan publish artikel pada waktu tertentu — atau UBAH waktu jadwal yang
     * sudah ada (reschedule, ditentukan dari status saat ini).
     *
     * Gerbang yang sama dengan publish() (skor ≥ 80 + minimal satu situs target)
     * dipasang SEKARANG, dan akan dicek ULANG saat jatuh tempo oleh publishDue(),
     * sehingga artikel yang diedit setelah dijadwalkan tetap terjaga kualitasnya.
     *
     * @throws ValidationException
     */
    public function schedule(Article $article, DateTimeInterface $at): CarbonInterface
    {
        $scheduledAt = Carbon::instance($at);
        $wasScheduled = $article->isScheduled();

        if ($article->status === 'queued') {
            throw ValidationException::withMessages([
                'status' => 'Artikel sedang diproses di antrean publish. Tunggu hingga selesai sebelum menjadwalkan.',
            ]);
        }

        $earliest = now()->addMinutes(self::MIN_LEAD_MINUTES);
        $latest = now()->addDays(self::MAX_HORIZON_DAYS);

        if ($scheduledAt->lessThan($earliest)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Waktu jadwal minimal '.self::MIN_LEAD_MINUTES.' menit dari sekarang.',
            ]);
        }

        if ($scheduledAt->greaterThan($latest)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Waktu jadwal maksimal 1 tahun ('.self::MAX_HORIZON_DAYS.' hari) dari sekarang.',
            ]);
        }

        $this->assertPublishable($article);

        $article->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        Log::info($wasScheduled ? '[ArticleService] reschedule' : '[ArticleService] schedule', [
            'article_id' => $article->id,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'site_count' => $article->sitePublications()->count(),
        ]);

        return $article->scheduled_at;
    }

    /**
     * Batalkan jadwal publish. Status kembali ke agregat wajar: draft bila belum
     * ada situs terbit, atau published bila artikel ternyata sudah live (dibatalkan
     * setelah sempat dijadwalkan ulang di atas artikel yang sudah terbit).
     *
     * @throws ValidationException
     */
    public function cancelSchedule(Article $article): Article
    {
        if (! $article->isScheduled()) {
            throw ValidationException::withMessages([
                'status' => 'Artikel tidak sedang dalam jadwal publish.',
            ]);
        }

        $statuses = $article->sitePublications()->pluck('status');

        $target = match (true) {
            $statuses->isEmpty() => 'draft',
            $statuses->every(fn ($s) => $s === 'published') => 'published',
            $statuses->contains('failed') => 'failed',
            $statuses->contains('queued') => 'queued',
            default => 'draft',
        };

        $article->update(['status' => $target, 'scheduled_at' => null]);

        Log::info('[ArticleService] cancelSchedule', [
            'article_id' => $article->id,
            'status_after' => $target,
        ]);

        return $article->refresh();
    }

    /**
     * Klaim & antrekan seluruh artikel yang jadwalnya sudah jatuh tempo.
     *
     * Dirancang IDEMPOTEN untuk cron per-menit: klaim memakai UPDATE bersyarat
     * (WHERE status = 'scheduled'), sehingga dua run bersamaan tidak akan
     * menggandakan dispatch. Jatuh tempo yang terlewat (server sempat mati) tetap
     * terkirim pada run berikutnya karena query selalu memakai scheduled_at <= now().
     *
     * @return array<int, int> ID artikel yang berhasil diantrekan
     */
    public function publishDue(int $limit = 50): array
    {
        $dueIds = Article::withoutGlobalScope(TenantScope::class)
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(max(1, $limit))
            ->pluck('id')
            ->all();

        $processed = [];

        foreach ($dueIds as $articleId) {
            // Klaim atomik: hanya proses yang memenangkan UPDATE lanjut dispatch.
            $claimed = Article::withoutGlobalScope(TenantScope::class)
                ->whereKey($articleId)
                ->where('status', 'scheduled')
                ->update(['status' => 'queued']);

            if ($claimed === 0) {
                continue; // sudah diklaim proses lain (cron kembar) — jangan digandakan.
            }

            $article = Article::withoutGlobalScope(TenantScope::class)
                ->with(['seoMeta', 'sitePublications'])
                ->find($articleId);

            if ($article === null) {
                continue;
            }

            try {
                // Gerbang dicek ULANG saat jatuh tempo: skor bisa turun bila
                // artikel diedit setelah dijadwalkan.
                $this->queuePublication($article, $this->assertPublishable($article));
                $processed[] = $articleId;
            } catch (ValidationException $validation) {
                // GATE DITOLAK → hentikan siklus (status failed, bukan loop per menit).
                $article->update(['status' => 'failed']);

                Log::warning('[ArticleScheduler] Jadwal jatuh tempo diblokir gerbang publish', [
                    'article_id' => $articleId,
                    'errors' => $validation->errors(),
                ]);
            } catch (\Throwable $throwable) {
                $article->update(['status' => 'failed']);

                Log::error('[ArticleScheduler] Gagal memproses jadwal jatuh tempo', [
                    'article_id' => $articleId,
                    'exception' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                ]);
            }
        }

        if ($processed !== []) {
            Log::info('[ArticleScheduler] Jadwal jatuh tempo diantrekan', ['article_ids' => $processed]);
        }

        return $processed;
    }

    /**
     * Gerbang publikasi (server-side): hitung ulang skor SEO + Readability dan
     * pastikan artikel punya minimal satu situs target. Dipakai bersama oleh
     * publish(), schedule(), dan publishDue() — satu sumber kebenaran (DRY).
     *
     * @return array<string, mixed> hasil analisis SEO terbaru
     *
     * @throws ValidationException
     */
    private function assertPublishable(Article $article): array
    {
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

        if ($article->sitePublications()->doesntExist()) {
            throw ValidationException::withMessages([
                'wp_site_ids' => 'Artikel belum memiliki situs WordPress target.',
            ]);
        }

        return $analysis;
    }

    /**
     * Antrekan publikasi ke seluruh situs target: perbarui skor hasil analisis
     * terbaru, tandai status queued (jadwal aktif dibersihkan), lalu dispatch satu
     * job per situs agar kegagalan satu situs tidak menggagalkan yang lain.
     *
     * @param  array<string, mixed>  $analysis
     */
    private function queuePublication(Article $article, array $analysis): void
    {
        $siteIds = $article->sitePublications()->pluck('wp_site_id');

        DB::transaction(function () use ($article, $analysis, $siteIds): void {
            $article->update([
                'seo_score' => $analysis['seo_score'],
                'readability_score' => $analysis['readability_score'],
                'status' => 'queued',
                'scheduled_at' => null,
            ]);

            $article->sitePublications()
                ->whereIn('wp_site_id', $siteIds)
                ->update(['status' => 'queued']);
        });

        // Satu job per situs target agar kegagalan satu situs tidak menggagalkan yang lain.
        foreach ($siteIds as $siteId) {
            PublishArticleToWordPressJob::dispatch($article->id, (int) $siteId);
        }

        Log::info('[ArticleService] queuePublication', [
            'article_id' => $article->id,
            'site_count' => $siteIds->count(),
        ]);
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
            // Kegagalan tingkat ARTIKEL (mis. gerbang jatuh tempo ditolak → status
            // failed tanpa baris situs yang ber-status failed): antrekan ulang lewat
            // gerbang publish penuh agar skor & situs divalidasi ulang.
            if ($siteId === null && $article->status === 'failed' && $article->sitePublications()->exists()) {
                return $this->publish($article);
            }

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
        // Jadwal aktif punya otoritas sendiri: baris per-situs masih 'draft' dan
        // tidak boleh menimpa status 'scheduled' (mis. saat situs target dihapus).
        if ($article->isScheduled()) {
            return;
        }

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
     * Event kalender satu bulan untuk modul artikel, terisolasi pada satu company:
     *  - 'scheduled' : jadwal publish aktif pada bulan tersebut
     *  - 'published' : riwayat terbit (waktu = sync pertama pada bulan tersebut)
     *  - 'failed'    : riwayat gagal (waktu = update terakhir artikel)
     *
     * @param  CarbonInterface  $start  awal bulan (sudah startOfMonth)
     * @param  CarbonInterface  $end    akhir bulan (sudah endOfMonth)
     * @return array<int, array<string, mixed>>
     */
    public function calendarEvents(int $companyId, CarbonInterface $start, CarbonInterface $end): array
    {
        $withSiteNames = ['sitePublications.wpSite' => fn ($q) => $q
            ->withoutGlobalScope(TenantScope::class)
            ->where('company_id', $companyId)];

        $events = [];

        // 1) Jadwal aktif pada bulan tersebut.
        Article::withoutGlobalScope(TenantScope::class)
            ->with($withSiteNames)
            ->where('company_id', $companyId)
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end])
            ->get()
            ->each(function (Article $article) use (&$events): void {
                $events[] = $this->calendarEvent(
                    $article,
                    'scheduled',
                    $article->scheduled_at,
                    $this->siteNamesForCalendar($article, 'scheduled'),
                );
            });

        // 2) Riwayat terbit: publikasi dengan synced_at pada bulan tersebut.
        Article::withoutGlobalScope(TenantScope::class)
            ->with($withSiteNames)
            ->where('company_id', $companyId)
            ->whereHas('sitePublications', fn ($q) => $q
                ->where('status', 'published')
                ->whereBetween('synced_at', [$start, $end]))
            ->get()
            ->each(function (Article $article) use (&$events, $start, $end): void {
                $published = $article->sitePublications
                    ->where('status', 'published')
                    ->filter(fn (ArticleSitePublication $pub) => $pub->synced_at !== null
                        && $pub->synced_at->betweenIncluded($start, $end));

                if ($published->isEmpty()) {
                    return;
                }

                $events[] = $this->calendarEvent(
                    $article,
                    'published',
                    $published->min(fn (ArticleSitePublication $pub) => $pub->synced_at),
                    $published->pluck('wpSite.site_name')->filter()->values()->all(),
                );
            });

        // 3) Riwayat gagal: artikel berstatus failed yang diperbarui pada bulan tersebut.
        Article::withoutGlobalScope(TenantScope::class)
            ->with($withSiteNames)
            ->where('company_id', $companyId)
            ->where('status', 'failed')
            ->whereBetween('updated_at', [$start, $end])
            ->get()
            ->each(function (Article $article) use (&$events): void {
                $events[] = $this->calendarEvent(
                    $article,
                    'failed',
                    $article->updated_at,
                    $this->siteNamesForCalendar($article, 'failed'),
                );
            });

        usort($events, fn (array $a, array $b) => strcmp($a['start'], $b['start']));

        return $events;
    }

    /**
     * Rakit satu event kalender dari artikel + waktu kejadiannya.
     *
     * @param  CarbonInterface|\DateTimeInterface  $at
     * @param  array<int, string>  $siteNames
     * @return array<string, mixed>
     */
    private function calendarEvent(Article $article, string $status, CarbonInterface $at, array $siteNames): array
    {
        return [
            'id' => $status.'-'.$article->id,
            'article_id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'status' => $status,
            'date' => $at->format('Y-m-d'),
            'time' => $at->format('H:i'),
            'start' => $at->toIso8601String(),
            'sites' => $siteNames,
            'edit_url' => route('articles.edit', $article),
        ];
    }

    /**
     * Nama situs terkait untuk subtitle event kalender, sesuai status event.
     *
     * @return array<int, string>
     */
    private function siteNamesForCalendar(Article $article, string $status): array
    {
        return $article->sitePublications
            ->filter(fn (ArticleSitePublication $pub) => $status === 'scheduled'
                ? true
                : $pub->status === $status)
            ->pluck('wpSite.site_name')
            ->filter()
            ->values()
            ->all();
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
