<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleWPLog;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\WPSite;
use App\Services\SeoAnalyzerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SeoAnalyzerService $seoAnalyzer,
    ) {
    }

    /**
     * Bangun data dashboard berdasarkan role dan tenant aktif.
     */
    public function index(Request $request): View
    {
        $user = $request->user()?->loadMissing(['roles', 'company']);
        abort_if(! $user, 403);

        $activeTenant = $this->resolveActiveTenant($request, $user);
        $companyContext = $this->buildCompanyContext($user, $activeTenant);

        // 1. Query Articles
        $articlesQuery = Article::query()->with(['author', 'company', 'wpSite', 'category', 'subCategory', 'logs']);

        if ($activeTenant['id'] !== null) {
            $articlesQuery->where('company_id', $activeTenant['id']);
        }

        if ($user->hasRole('author')) {
            $articlesQuery->where('user_id', $user->id);
        }

        $articles = $articlesQuery->latest()->get();

        // 2. Query Logs
        $logsQuery = ArticleWPLog::query()->with(['article.company', 'article.author', 'wpSite'])->latest();

        if ($activeTenant['id'] !== null) {
            $logsQuery->whereHas('article', fn ($query) => $query->where('company_id', $activeTenant['id']));
        }

        if ($user->hasRole('author')) {
            $logsQuery->whereHas('article', fn ($query) => $query->where('user_id', $user->id));
        }

        $logs = $logsQuery->limit(15)->get();

        $selectedCompanyId = $activeTenant['id'] ?? $companyContext['selected_company']?->id;

        // 3. Query Master Data
        $categories = Category::query()
            ->when($selectedCompanyId, fn ($query) => $query->where('company_id', $selectedCompanyId))
            ->orderBy('name')
            ->get();

        $wpSites = WPSite::query()
            ->when($selectedCompanyId, fn ($query) => $query->where('company_id', $selectedCompanyId))
            ->orderBy('site_name')
            ->get();

        $targetUser = $user;
        $targetCompanyId = $selectedCompanyId ?? ($user->company_id ?? 0);

        // 4. Hitung Metrics Dashboard
        $metrics = [
            'companies_total' => $activeTenant['id'] === null
                ? Company::query()->count()
                : Company::query()->whereKey($activeTenant['id'])->count(),
            'users_total' => User::query()
                ->when($activeTenant['id'], fn ($query) => $query->where('company_id', $activeTenant['id']))
                ->count(),
            'articles_total' => $articles->count(),
            'avg_seo_score' => $articles->count() > 0 ? (int) round($articles->avg('seo_score')) : 0,
            'draft_total' => $articles->where('status', 'draft')->count(),
            'queued_total' => $articles->where('status', 'queued')->count(),
            'published_total' => $articles->where('status', 'published')->count(),
            'failed_total' => $articles->where('status', 'failed')->count(),
        ];

        // 5. Analisis SEO Preview untuk Form
        $preview = $this->seoAnalyzer->analyze([
            'title' => old('title', ''),
            'slug' => old('slug', ''),
            'content' => old('content', ''),
            'yoast_title' => old('yoast_title', ''),
            'yoast_metadesc' => old('yoast_metadesc', ''),
            'yoast_focuskw' => old('yoast_focuskw', ''),
            'image_alt_text' => old('image_alt_text', ''),
        ]);

        return view('dashboard', [
            'currentUser' => $user,
            'companies' => $companyContext['switcher_companies'],
            'selectedCompany' => $companyContext['selected_company'],
            'selectedUser' => $targetUser,
            'categories' => $categories,
            'wpSites' => $wpSites,
            'articles' => $articles,
            'logs' => $logs,
            'metrics' => $metrics,
            'preview' => $preview,
            'activeTenant' => $activeTenant,
            'activeCompanyIdForForm' => $targetCompanyId,
        ]);
    }

    /**
     * Tentukan tenant mana yang sedang aktif untuk diproses.
     */
    private function resolveActiveTenant(Request $request, User $user): array
    {
        if ($user->hasRole('super_admin')) {
            $sessionCompanyId = $request->session()->get('active_company_id', 'all');

            if ($sessionCompanyId === null || $sessionCompanyId === 'all') {
                return [
                    'id' => null,
                    'label' => 'All Companies',
                    'mode' => 'all',
                ];
            }

            $company = Company::query()->find((int) $sessionCompanyId);

            if (! $company) {
                $request->session()->put('active_company_id', 'all');

                return [
                    'id' => null,
                    'label' => 'All Companies',
                    'mode' => 'all',
                ];
            }

            return [
                'id' => (int) $company->id,
                'label' => $company->name,
                'mode' => 'single',
            ];
        }

        // Untuk Non-Superadmin (Admin / Author)
        $company = $user->company;

        return [
            'id' => $company?->id,
            'label' => $company?->name ?? 'Unassigned Tenant',
            'mode' => 'fixed',
        ];
    }

    /**
     * Bangun konteks perusahaan untuk dropdown switcher UI.
     */
    private function buildCompanyContext(User $user, array $activeTenant): array
    {
        if ($user->hasRole('super_admin')) {
            $switcherCompanies = Company::query()->orderBy('name')->get();
            $selectedCompany = $activeTenant['id'] ? $switcherCompanies->firstWhere('id', $activeTenant['id']) : null;

            return [
                'switcher_companies' => $switcherCompanies,
                'selected_company' => $selectedCompany,
            ];
        }

        // Untuk Non-Superadmin
        $fixedCompany = $user->company;

        return [
            'switcher_companies' => collect([$fixedCompany])->filter(),
            'selected_company' => $fixedCompany,
        ];
    }
}
