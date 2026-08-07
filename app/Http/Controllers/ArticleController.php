<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Models\Tag;
use App\Models\User;
use App\Models\WPSite;
use App\Services\ArticleService;
use App\Support\ArticleContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articleService) {}

    /**
     * Halaman utama artikel.
     * - Super admin tanpa perusahaan aktif → tampilkan kartu pemilihan perusahaan.
     * - Selain itu → daftar artikel yang terisolasi pada perusahaan aktif.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Super admin harus memilih perusahaan dulu (fokus modul artikel belum diset).
        if ($user->isSuperAdmin() && ! ArticleContext::hasCompany()) {
            $companies = Company::query()
                ->withCount([
                    // withoutGlobalScope agar hitungan tidak ikut terfilter scope global
                    // (active_company_id) yang kini INDEPENDEN dari konteks artikel.
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

        return view('articles.index', compact('company', 'articles'));
    }

    /**
     * Super admin memilih perusahaan dari kartu → set FOKUS modul artikel di sesi
     * (bukan scope global), lalu diarahkan ke halaman index perusahaan tersebut.
     */
    public function chooseCompany(Request $request, Company $company): RedirectResponse
    {
        // Hanya super admin yang boleh berpindah fokus artikel. Admin/Author terkunci di company sendiri.
        abort_unless($request->user()->isSuperAdmin(), 403);

        ArticleContext::setCompany($company->id);

        return redirect()->route('articles.index');
    }

    /**
     * Tombol "Ganti Perusahaan" → lupakan fokus artikel saat ini sehingga super admin
     * kembali ke halaman pemilihan perusahaan. Scope global tidak tersentuh.
     */
    public function selectCompany(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        ArticleContext::forget();

        return redirect()->route('articles.index');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $company = ArticleContext::companyId() ? Company::find(ArticleContext::companyId()) : null;

        // Super admin belum memilih perusahaan konkret → kembalikan ke pemilihan.
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
        // Article::resolveRouteBinding mengunci artikel pada perusahaan konteks artikel
        // (ArticleContext); artikel lintas-perusahaan tidak akan pernah ter-resolve di sini.
        $article->load(['seoMeta', 'categories', 'tags', 'sitePublications']);
        $company = Company::findOrFail($article->company_id);

        return view('articles.edit', array_merge(
            ['company' => $company, 'article' => $article],
            $this->companyFormData($article->company_id),
        ));
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = $this->articleService->store($request->validated(), $request->file('featured_image'));

        if ($request->string('action')->toString() === 'publish') {
            $this->articleService->publish($article);

            return redirect()->route('articles.index')->with('success', 'Artikel masuk antrean publish.');
        }

        return redirect()->route('articles.index')->with('success', 'Draft berhasil disimpan.');
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article = $this->articleService->update($article, $request->validated(), $request->file('featured_image'));

        if ($request->string('action')->toString() === 'publish') {
            $this->articleService->publish($article);

            return redirect()->route('articles.index')->with('success', 'Artikel diperbarui & masuk antrean publish.');
        }

        return redirect()->route('articles.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function publish(Article $article): RedirectResponse
    {
        $this->articleService->publish($article);

        return back()->with('success', 'Artikel berhasil masuk antrean publish.');
    }

    public function retry(Article $article): RedirectResponse
    {
        $this->articleService->retry($article);

        return back()->with('success', 'Retry publish telah dijalankan.');
    }

    /**
     * Hapus artikel (record lokal + post WordPress di semua situs target terpublikasi).
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->articleService->delete($article);

        return redirect()->route('articles.index')
            ->with('success', 'Artikel berhasil dihapus beserta post-nya dari situs WordPress target.');
    }

    /**
     * Hapus/unpublish publikasi artikel pada SATU situs WordPress.
     */
    public function destroySite(Article $article, int $wpSite): RedirectResponse
    {
        $publication = $article->sitePublications()
            ->where('wp_site_id', $wpSite)
            ->with('wpSite')
            ->firstOrFail();

        $this->articleService->deleteSitePublication($article, $publication);

        return back()->with('success', 'Publikasi dihapus dari situs "'.($publication->wpSite->site_name ?? '#'.$wpSite).'".');
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
            // withoutGlobalScope: konteks artikel kini independen dari active_company_id,
            // jadi isolasi ditegakkan lewat filter eksplisit where('company_id', ...) di sini.
            'categories' => Category::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->orderBy('name')->get(),
            'tags' => Tag::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->orderBy('name')->get(),
            'wpSites' => WPSite::query()->withoutGlobalScope(TenantScope::class)->where('company_id', $companyId)->with('categories')->orderBy('site_name')->get(),
            // Author = user pada company aktif. Dipakai untuk sinkronisasi author WordPress
            // (WpAuthorResolverService memetakan `username` user ini ke WP user saat publish).
            'authors' => User::query()->where('company_id', $companyId)->orderBy('name')->get(),
        ];
    }
}
