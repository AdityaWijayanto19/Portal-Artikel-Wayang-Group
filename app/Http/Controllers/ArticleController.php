<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Models\Article;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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
                    'score' => (int) ($article->seoMeta->seo_score ?? ($article->seo_score ?? 0)),
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

            return redirect()->route('articles.index')->with('success', 'Draft berhasil disimpan.');
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

            return redirect()->route('articles.index')->with('success', 'Artikel berhasil diperbarui.');
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
            ? optional(WPSite::query()->withoutGlobalScope(TenantScope::class)->find($wpSite))->site_name
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
