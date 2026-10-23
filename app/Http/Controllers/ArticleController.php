<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Models\Article;
use App\Models\ArticleSeoMeta;
use App\Models\ArticleSitePublication;
use App\Models\Category;
use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Models\Tag;
use App\Models\User;
use App\Models\WPSite;
use App\Services\ActivityLogger;
use App\Services\ArticleService;
use App\Support\ActivityAction;
use App\Support\ArticleContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin() && ! ArticleContext::hasCompany()) {
            $companies = Company::query()
                ->withCount([
                    'articles' => fn ($q) => $q->withoutGlobalScope(TenantScope::class),
                    'wpSites' => fn ($q) => $q->withoutGlobalScope(TenantScope::class),
                    'users',
                ])
                ->orderBy('name')
                ->get();

            return view('articles.select-company', compact('companies'));
        }

        $companyId = ArticleContext::companyId();
        abort_if($companyId === null, 403, 'Perusahaan tidak dapat ditentukan.');

        $company = Company::findOrFail($companyId);
        $articles = $this->articleService->paginateForCompany($companyId, $request->string('search')->toString() ?: null);

        // Snapshot awal untuk monitor status realtime di sisi klien (smart polling).
        $monitorArticles = $articles->getCollection()->mapWithKeys(function (Article $article) {
            return [
                (string) $article->id => [
                    'status' => $article->status,
                    'scheduled_at' => $article->scheduled_at?->toIso8601String(),
                    'score' => (int) ($article->seoMeta->seo_score ?? ($article->seo_score ?? 0)),
                    'readability_score' => (int) ($article->seoMeta->readability_score ?? ($article->readability_score ?? 0)),
                    'pubs' => $article->sitePublications->mapWithKeys(fn ($pub) => [
                        (string) $pub->wp_site_id => [
                            'status' => $pub->status,
                            'wp_post_id' => $pub->wp_post_id,
                            'published_url' => $pub->published_url,
                            'site_name' => $pub->wpSite?->site_name,
                            'site_url' => $pub->wpSite?->site_url,
                        ],
                    ])->all(),
                ],
            ];
        })->all();

        return view('articles.index', compact('company', 'articles', 'monitorArticles'));
    }

    /**
     * Endpoint ringan untuk smart polling status publikasi artikel di halaman index.
     *
     * Hanya mengembalikan status artikel + publikasi per-situs untuk id yang
     * diminta (maks 50) dan DIISOLASI pada perusahaan konteks (ArticleContext),
     * sehingga user tidak bisa membaca status artikel milik perusahaan lain.
     */
    public function publicationsStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_ids' => ['present', 'array', 'max:50'],
            'article_ids.*' => ['integer', 'min:1'],
        ]);

        $companyId = ArticleContext::companyId();
        abort_if($companyId === null, 403, 'Perusahaan tidak dapat ditentukan.');

        $ids = array_values(array_unique(array_map('intval', $validated['article_ids'])));

        if (empty($ids)) {
            return response()->json(['articles' => new \stdClass]);
        }

        $articles = Article::withoutGlobalScope(TenantScope::class)
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->get(['id', 'status']);

        if ($articles->isEmpty()) {
            return response()->json(['articles' => new \stdClass]);
        }

        $publications = ArticleSitePublication::query()
            ->whereIn('article_id', $articles->pluck('id'))
            ->with(['wpSite' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->select('id', 'site_name', 'site_url')])
            ->get(['article_id', 'wp_site_id', 'status', 'wp_post_id', 'published_url'])
            ->groupBy('article_id');

        $result = $articles->mapWithKeys(function (Article $article) use ($publications) {
            return [
                (string) $article->id => [
                    'status' => $article->status,
                    'publications' => collect($publications[$article->id] ?? [])->mapWithKeys(fn ($pub) => [
                        (string) $pub->wp_site_id => [
                            'status' => $pub->status,
                            'wp_post_id' => $pub->wp_post_id,
                            'published_url' => $pub->published_url,
                            'site_name' => $pub->wpSite?->site_name,
                            'site_url' => $pub->wpSite?->site_url,
                        ],
                    ])->all(),
                ],
            ];
        });

        Log::info('[ArticleMonitor] publicationsStatus', [
            'requested_ids' => $ids,
            'returned' => $result->count(),
            'statuses' => $articles->pluck('status', 'id')->all(),
        ]);

        return response()->json(['articles' => $result])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Cek duplikasi focus keyword secara real-time.
     * Digunakan oleh frontend via AJAX saat user mengisi focus keyword.
     */
    public function checkKeywordDuplicate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'article_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $companyId = ArticleContext::companyId();
        abort_if($companyId === null, 403, 'Perusahaan tidak dapat ditentukan.');

        $keyword = trim($validated['keyword']);
        $articleId = $validated['article_id'] ?? null;

        // 1) Peringatan duplikat: hanya di dalam perusahaan aktif (scope endpoint).
        $query = ArticleSeoMeta::withoutGlobalScope(TenantScope::class)
            ->where('yoast_focuskw', $keyword)
            ->whereHas('article', fn ($q) => $q->where('company_id', $companyId));

        if ($articleId !== null) {
            $query->where('article_id', '!=', $articleId);
        }

        $exists = $query->exists();

        // 2) Kondisi untuk PENILAIAN skor: HARUS identik dengan
        // SeoAnalyzerService::scorePreviouslyUsedKeyphrase (tanpa filter perusahaan,
        // dan mengabaikan meta yatim milik artikel yang sudah terhapus).
        // Bila beda, kalkulator JS di form menampilkan skor berbeda dari gerbang publish.
        $scoreQuery = ArticleSeoMeta::withoutGlobalScope(TenantScope::class)
            ->where('yoast_focuskw', $keyword)
            ->whereIn('article_id', DB::table('articles')->select('id'));

        if ($articleId !== null) {
            $scoreQuery->where('article_id', '!=', $articleId);
        }

        $scoreDuplicate = $scoreQuery->exists();

        return response()->json([
            'duplicate' => $exists,
            'score_duplicate' => $scoreDuplicate,
            'keyword' => $keyword,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function chooseCompany(Request $request, Company $company): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        ArticleContext::setCompany($company->id);

        return redirect()->route('articles.index');
    }

    public function selectCompany(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        ArticleContext::forget();

        return redirect()->route('articles.index');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $company = ArticleContext::companyId() ? Company::find(ArticleContext::companyId()) : null;

        if ($company === null) {
            return redirect()->route('articles.index');
        }

        return view('articles.create', array_merge(
            ['company' => $company],
            $this->companyFormData($company->id),
        ));
    }

    public function edit(Article $article): View
    {
        $article->load(['seoMeta', 'categories', 'tags', 'sitePublications']);
        $company = Company::findOrFail($article->company_id);

        return view('articles.edit', array_merge(
            ['company' => $company, 'article' => $article],
            $this->companyFormData($article->company_id),
        ));
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        try {
            $start = microtime(true);
            $article = $this->articleService->store($request->validated(), $request->file('featured_image'));
            $storeElapsed = round((microtime(true) - $start) * 1000, 2);

            if ($request->string('action')->toString() === 'publish') {
                $this->articleService->publish($article);

                $this->activityLogger->log(
                    ActivityAction::ARTICLE_CREATED,
                    "Membuat artikel \"{$article->title}\".",
                    subject: $article,
                );

                Log::info('[Article] store+publish selesai', [
                    'article_id' => $article->id,
                    'has_image' => $request->hasFile('featured_image'),
                    'content_length' => strlen((string) $request->input('content')),
                    'store_ms' => $storeElapsed,
                    'total_ms' => round((microtime(true) - $start) * 1000, 2),
                ]);

                return redirect()->route('articles.index')->with('success', 'Artikel masuk antrean publish.');
            }

            if ($request->string('action')->toString() === 'schedule') {
                $this->articleService->schedule($article, Carbon::parse((string) $request->input('scheduled_at')));

                $this->activityLogger->log(
                    ActivityAction::ARTICLE_SCHEDULED,
                    "Menjadwalkan publish artikel \"{$article->title}\" pada {$this->formatScheduleTime($article)}.",
                    subject: $article,
                    properties: ['scheduled_at' => $article->scheduled_at?->toIso8601String()],
                );

                Log::info('[Article] store+schedule selesai', [
                    'article_id' => $article->id,
                    'scheduled_at' => $article->scheduled_at?->toIso8601String(),
                    'has_image' => $request->hasFile('featured_image'),
                ]);

                return redirect()->route('articles.index')
                    ->with('success', 'Artikel dijadwalkan publish pada '.$this->formatScheduleTime($article).' WIB.');
            }

            $this->activityLogger->log(
                ActivityAction::ARTICLE_CREATED,
                "Membuat draft artikel \"{$article->title}\".",
                subject: $article,
            );

            Log::info('[Article] store draft selesai', [
                'article_id' => $article->id,
                'has_image' => $request->hasFile('featured_image'),
                'content_length' => strlen((string) $request->input('content')),
                'store_ms' => $storeElapsed,
                'total_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);

            return $request->boolean('_force_draft')
                ? redirect()->route('articles.edit', $article)->with('success', 'Draft berhasil disimpan.')
                : redirect()->route('articles.index')->with('success', 'Draft berhasil disimpan.');
        } catch (ValidationException $validation) {
            // Gerbang publish/jadwal ditolak (skor < 80, jadwal terlalu dekat, dsb.)
            // → tampilkan pesan validasi apa adanya, bukan error sistem generik.
            Log::info('[Article] store ditolak gerbang validasi', ['errors' => $validation->errors()]);

            return back()->withErrors($validation->errors())->withInput();
        } catch (\Throwable $th) {
            Log::error('Gagal menambahkan artikel: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menambahkan artikel.')
                ->withInput();
        }
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        try {
            $start = microtime(true);
            $article = $this->articleService->update($article, $request->validated(), $request->file('featured_image'));
            $storeElapsed = round((microtime(true) - $start) * 1000, 2);

            if ($request->string('action')->toString() === 'publish') {
                $this->articleService->publish($article);

                $this->activityLogger->log(
                    ActivityAction::ARTICLE_UPDATED,
                    "Memperbarui artikel \"{$article->title}\".",
                    subject: $article,
                );

                Log::info('[Article] update+publish selesai', [
                    'article_id' => $article->id,
                    'has_image' => $request->hasFile('featured_image'),
                    'update_ms' => $storeElapsed,
                    'total_ms' => round((microtime(true) - $start) * 1000, 2),
                ]);

                return redirect()->route('articles.index')->with('success', 'Artikel diperbarui & masuk antrean publish.');
            }

            if ($request->string('action')->toString() === 'schedule') {
                $wasScheduled = $article->isScheduled();
                $this->articleService->schedule($article, Carbon::parse((string) $request->input('scheduled_at')));

                $this->activityLogger->log(
                    $wasScheduled ? ActivityAction::ARTICLE_RESCHEDULED : ActivityAction::ARTICLE_SCHEDULED,
                    ($wasScheduled ? 'Mengubah jadwal publish artikel' : 'Menjadwalkan publish artikel')
                        ." \"{$article->title}\" pada {$this->formatScheduleTime($article)}.",
                    subject: $article,
                    properties: ['scheduled_at' => $article->scheduled_at?->toIso8601String()],
                );

                Log::info('[Article] update+schedule selesai', [
                    'article_id' => $article->id,
                    'scheduled_at' => $article->scheduled_at?->toIso8601String(),
                    'was_scheduled' => $wasScheduled,
                ]);

                return redirect()->route('articles.index')
                    ->with('success', 'Jadwal publish disimpan: '.$this->formatScheduleTime($article).' WIB.');
            }

            $this->activityLogger->log(
                ActivityAction::ARTICLE_UPDATED,
                "Memperbarui draft artikel \"{$article->title}\".",
                subject: $article,
            );

            Log::info('[Article] update draft selesai', [
                'article_id' => $article->id,
                'has_image' => $request->hasFile('featured_image'),
                'update_ms' => $storeElapsed,
                'total_ms' => round((microtime(true) - $start) * 1000, 2),
            ]);

            return $request->boolean('_force_draft')
                ? redirect()->route('articles.edit', $article)->with('success', 'Draft berhasil disimpan.')
                : redirect()->route('articles.index')->with('success', 'Artikel berhasil diperbarui.');
        } catch (ValidationException $validation) {
            // Gerbang publish/jadwal ditolak → pesan validasi apa adanya.
            Log::info('[Article] update ditolak gerbang validasi', ['errors' => $validation->errors()]);

            return back()->withErrors($validation->errors())->withInput();
        } catch (\Throwable $th) {
            Log::error('Gagal memperbarui artikel: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal memperbarui artikel.')
                ->withInput();
        }
    }

    public function publish(Article $article): RedirectResponse
    {
        $this->articleService->publish($article);

        return back()->with('success', 'Artikel berhasil masuk antrean publish.');
    }

    /**
     * Retry publish. Tanpa $wpSite → hanya situs berstatus failed yang di-retry.
     * Dengan $wpSite → retry satu situs tertentu (untuk tombol per-situs).
     */
    public function retry(Article $article, ?int $wpSite = null): RedirectResponse
    {
        $siteName = $wpSite !== null
            ? $article->sitePublications()
                ->where('wp_site_id', $wpSite)
                ->with('wpSite')
                ->first()
                ?->wpSite?->site_name
            : null;

        $this->articleService->retry($article, $wpSite);

        return back()->with(
            'success',
            $siteName
                ? "Retry publish situs \"{$siteName}\" telah dijalankan."
                : 'Retry publish untuk situs-situs yang gagal telah dijalankan.',
        );
    }

    /**
     * Halaman Kalender Jadwal — month-view jadwal publish mendatang + riwayat
     * terbit/gagal. Data event diambil via AJAX (articles.schedule.events).
     */
    public function calendar(Request $request): View|RedirectResponse
    {
        $companyId = ArticleContext::companyId();

        if ($companyId === null) {
            abort_unless($request->user()?->isSuperAdmin(), 403, 'Perusahaan tidak dapat ditentukan.');

            // Super admin belum memilih perusahaan fokus → kembali ke pemilih.
            return redirect()->route('articles.index');
        }

        $company = Company::findOrFail($companyId);

        return view('articles.calendar', compact('company'));
    }

    /**
     * JSON event kalender per bulan, terisolasi pada perusahaan konteks artikel.
     */
    public function scheduleEvents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $companyId = ArticleContext::companyId();
        abort_if($companyId === null, 403, 'Perusahaan tidak dapat ditentukan.');

        $start = Carbon::create((int) $validated['year'], (int) $validated['month'], 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return response()
            ->json(['events' => $this->articleService->calendarEvents($companyId, $start, $end)])
            ->header('Cache-Control', 'no-store, private');
    }

    /**
     * Ubah waktu jadwal publish (reschedule) — dipakai kalender (drag & drop)
     * dan endpoint aksi cepat lain. Mendukung respons JSON maupun redirect biasa.
     */
    public function updateSchedule(Request $request, Article $article): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
        ]);

        $wasScheduled = $article->isScheduled();

        $this->articleService->schedule($article, Carbon::parse((string) $validated['scheduled_at']));

        $this->activityLogger->log(
            $wasScheduled ? ActivityAction::ARTICLE_RESCHEDULED : ActivityAction::ARTICLE_SCHEDULED,
            ($wasScheduled ? 'Mengubah jadwal publish artikel' : 'Menjadwalkan publish artikel')
                ." \"{$article->title}\" pada {$this->formatScheduleTime($article)}.",
            subject: $article,
            properties: ['scheduled_at' => $article->scheduled_at?->toIso8601String()],
        );

        $message = 'Jadwal publish dipindah ke '.$this->formatScheduleTime($article).' WIB.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'scheduled_at' => $article->scheduled_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Batalkan jadwal publish → artikel kembali ke status wajar (draft).
     */
    public function cancelSchedule(Request $request, Article $article): JsonResponse|RedirectResponse
    {
        $this->articleService->cancelSchedule($article);

        $this->activityLogger->log(
            ActivityAction::ARTICLE_SCHEDULE_CANCELLED,
            "Membatalkan jadwal publish artikel \"{$article->title}\".",
            subject: $article,
        );

        $message = 'Jadwal publish dibatalkan. Artikel kembali ke draft.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => $article->status]);
        }

        return back()->with('success', $message);
    }

    /**
     * Hapus artikel (record lokal + post WordPress di semua situs target terpublikasi).
     */
    public function destroy(Article $article): RedirectResponse
    {
        try {
            $articleTitle = $article->title;
            $this->articleService->delete($article);

            $this->activityLogger->log(
                ActivityAction::ARTICLE_DELETED,
                "Menghapus artikel \"{$articleTitle}\" beserta post-nya dari WordPress.",
                subject: $article,
            );

            return redirect()->route('articles.index')
                ->with('success', 'Artikel berhasil dihapus beserta post-nya dari situs WordPress target.');
        } catch (\Throwable $th) {
            Log::error('Gagal menghapus artikel: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menghapus artikel.')
                ->withInput();
        }
    }

    /**
     * Hapus/unpublish publikasi artikel pada SATU situs WordPress.
     */
    public function destroySite(Article $article, int $wpSite): RedirectResponse
    {
        try {
            $publication = $article->sitePublications()
                ->where('wp_site_id', $wpSite)
                ->with('wpSite')
                ->firstOrFail();

            $siteName = $publication->wpSite->site_name ?? '#'.$wpSite;
            $this->articleService->deleteSitePublication($article, $publication);

            $this->activityLogger->log(
                ActivityAction::ARTICLE_SITE_REMOVED,
                "Menghapus publikasi artikel \"{$article->title}\" dari situs \"{$siteName}\".",
                subject: $article,
                properties: ['wp_site_id' => $wpSite],
            );

            return back()->with('success', 'Publikasi dihapus dari situs "'.$siteName.'".');
        } catch (\Throwable $th) {
            Log::error('Gagal hapus publikasi situs: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal hapus publikasi situs.')
                ->withInput();
        }
    }

    /**
     * Format tampilan waktu jadwal (WIB) untuk pesan & activity log.
     */
    private function formatScheduleTime(Article $article): string
    {
        return $article->scheduled_at?->format('d M Y, H:i') ?? '-';
    }

    /**
     * Data pilihan form (kategori, tag, situs WP) yang DIISOLASI pada satu perusahaan.
     * Query eksplisit where company_id mencegah kebocoran data antar-tenant.
     *
     * @return array{categories: Collection, tags: Collection, wpSites: Collection}
     */
    private function companyFormData(int $companyId): array
    {
        return [
            'categories' => Category::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->orderBy('name')->get(),
            'tags' => Tag::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->orderBy('name')->get(),
            'wpSites' => WPSite::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->with('categories')->orderBy('site_name')->get(),
            'authors' => User::query()->where('company_id', $companyId)->orderBy('name')->get(),
        ];
    }
}
