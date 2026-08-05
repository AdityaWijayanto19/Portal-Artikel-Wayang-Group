<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWpSiteRequest;
use App\Models\Category;
use App\Models\Company;
use App\Models\WPSite;
use App\Services\WpSiteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WpSiteController extends Controller
{
    public function __construct(private readonly WpSiteService $wpSiteService)
    {
    }

    public function index(Request $request): View
    {
        $wpSites = WPSite::query()
            ->with('categories')
            ->when($request->search, function ($query, $search) {
                $query->where('site_name', 'like', "%{$search}%")
                    ->orWhere('site_url', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('wp-sites.index', compact('wpSites'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderBy('name')->get();
        $selectedCompanyId = $this->resolveSelectedCompanyIdForForm($companies);
        $categories = $this->resolveCategoriesForCurrentUser($selectedCompanyId);
        $allCategories = Category::withoutGlobalScopes()
            ->orderBy('name')
            ->get();

        return view('wp-sites.create', compact('companies', 'categories', 'allCategories', 'selectedCompanyId'));
    }

    public function store(StoreWpSiteRequest $request): RedirectResponse
    {
        $this->wpSiteService->create($request->validated());

        return redirect()->route('wp-sites.index')->with('success', 'WP Site berhasil ditambahkan.');
    }

    public function edit(WPSite $wpSite): View
    {
        $companies = Company::query()->orderBy('name')->get();
        $selectedCompanyId = $wpSite->company_id;
        $categories = $this->resolveCategoriesForCurrentUser($selectedCompanyId);
        $allCategories = Category::withoutGlobalScopes()
            ->orderBy('name')
            ->get();

        return view('wp-sites.edit', compact('wpSite', 'companies', 'categories', 'allCategories', 'selectedCompanyId'));
    }

    public function update(StoreWpSiteRequest $request, WPSite $wpSite): RedirectResponse
    {
        $this->wpSiteService->update($wpSite, $request->validated());

        return redirect()->route('wp-sites.index')->with('success', 'WP Site berhasil diperbarui.');
    }

    public function destroy(WPSite $wpSite): RedirectResponse
    {
        $this->wpSiteService->delete($wpSite);

        return redirect()->route('wp-sites.index')->with('success', 'WP Site berhasil dihapus.');
    }

    protected function resolveCategoriesForCurrentUser(?int $companyId = null): \Illuminate\Support\Collection
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            $targetCompanyId = $companyId ?? session('active_company_id');
        } else {
            $targetCompanyId = $companyId ?? $user?->company_id;
        }

        if (! $targetCompanyId) {
            return collect();
        }

        return Category::query()
            ->where('company_id', (int) $targetCompanyId)
            ->orderBy('name')
            ->get();
    }

    protected function resolveSelectedCompanyIdForForm($companies): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return $companies->first()?->id;
        }

        if (! $user->isSuperAdmin()) {
            return $user->company_id;
        }

        $oldCompanyId = old('company_id');

        if (is_numeric($oldCompanyId)) {
            return (int) $oldCompanyId;
        }

        $sessionCompanyId = session('active_company_id');

        if (is_numeric($sessionCompanyId)) {
            return (int) $sessionCompanyId;
        }

        return $companies->first()?->id;
    }
}
