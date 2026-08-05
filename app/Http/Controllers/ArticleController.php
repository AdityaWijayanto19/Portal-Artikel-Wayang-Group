<?php

namespace App\Http\Controllers;

use App\Http\Requests\Article\StoreArticleRequest;
use App\Http\Requests\Article\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\Tag;
use App\Models\User;
use App\Models\WPSite;
use App\Services\ArticleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articleService)
    {
    }

    /**
     * Halaman utama artikel.
     * - Super admin tanpa perusahaan aktif → tampilkan kartu pemilihan perusahaan.
     * - Selain itu → daftar artikel yang terisolasi pada perusahaan aktif.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Super admin harus memilih perusahaan dulu (belum ada / "Semua Perusahaan").
        if ($user->isSuperAdmin() && ! $this->hasConcreteActiveCompany()) {
            $companies = Company::query()
                ->withCount(['articles', 'wpSites', 'users'])
                ->orderBy('name')
                ->get();

            return view('articles.select-company', compact('companies'));
        }

        $companyId = $this->resolveActiveCompanyId();
        abort_if($companyId === null, 403, 'Perusahaan tidak dapat ditentukan.');

        $company = Company::findOrFail($companyId);
        $articles = $this->articleService->paginateForCompany($companyId, $request->string('search')->toString() ?: null);

        return view('articles.index', compact('company', 'articles'));
    }

    /**
     * Super admin memilih perusahaan dari kartu → set konteks tenant aktif di sesi,
     * lalu diarahkan ke halaman index perusahaan tersebut.
     */
    public function chooseCompany(Request $request, Company $company): RedirectResponse
    {
        // Hanya super admin yang boleh berpindah tenant. Admin/Author terkunci di company sendiri.
        abort_unless($request->user()->isSuperAdmin(), 403);

        $request->session()->put('active_company_id', $company->id);

        return redirect()->route('articles.index');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $company = $this->resolveActiveCompany();

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
        // TenantScope pada model Article menjamin artikel milik tenant aktif;
        // artikel lintas-perusahaan tidak akan pernah ter-resolve di sini.
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
     * Data pilihan form (kategori, tag, situs WP) yang DIISOLASI pada satu perusahaan.
     * Query eksplisit where company_id mencegah kebocoran data antar-tenant.
     *
     * @return array{categories: \Illuminate\Support\Collection, tags: \Illuminate\Support\Collection, wpSites: \Illuminate\Support\Collection}
     */
    private function companyFormData(int $companyId): array
    {
        return [
            'categories' => Category::query()->where('company_id', $companyId)->orderBy('name')->get(),
            'tags' => Tag::query()->where('company_id', $companyId)->orderBy('name')->get(),
            'wpSites' => WPSite::query()->where('company_id', $companyId)->orderBy('site_name')->get(),
            // Author = user pada company aktif. Dipakai untuk sinkronisasi author WordPress
            // (WpAuthorResolverService memetakan `username` user ini ke WP user saat publish).
            'authors' => User::query()->where('company_id', $companyId)->orderBy('name')->get(),
        ];
    }

    /**
     * Perusahaan aktif untuk konteks tenant saat ini (null jika belum konkret).
     */
    private function resolveActiveCompany(): ?Company
    {
        $companyId = $this->resolveActiveCompanyId();

        return $companyId ? Company::find($companyId) : null;
    }

    private function resolveActiveCompanyId(): ?int
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $active = session('active_company_id');

            return is_numeric($active) ? (int) $active : null;
        }

        return $user->primaryCompanyId();
    }

    private function hasConcreteActiveCompany(): bool
    {
        return is_numeric(session('active_company_id'));
    }
}
