<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesActivityLogs;
use App\Models\ArticleWPLog;
use App\Models\Scopes\TenantScope;
use App\Models\WPSite;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WpSyncLogController extends Controller
{
    use ScopesActivityLogs;

    /**
     * Riwayat sinkronisasi WordPress. Super admin melihat semua perusahaan,
     * admin hanya perusahaannya sendiri.
     *
     * TenantScope dilepas pada relasi article/wpSite: worker antrean menulis log
     * tanpa konteks Auth, dan super admin perlu membaca lintas tenant.
     */
    public function index(Request $request): View
    {
        $scopeCompanyId = $this->resolveLogScopeCompanyId($request->user());

        $logs = ArticleWPLog::query()
            ->with([
                'article' => fn ($query) => $query->withoutGlobalScope(TenantScope::class)->select(['id', 'company_id', 'user_id', 'title']),
                'article.company' => fn ($query) => $query->select(['id', 'name']),
                'article.author' => fn ($query) => $query->select(['id', 'name']),
                'wpSite' => fn ($query) => $query->withoutGlobalScope(TenantScope::class)->select(['id', 'company_id', 'site_name', 'site_url']),
            ])
            ->when($scopeCompanyId, function ($query) use ($scopeCompanyId) {
                $query->whereHas('article', fn ($sub) => $sub->withoutGlobalScope(TenantScope::class)->where('company_id', $scopeCompanyId));
            })
            ->when($request->filled('wp_site_id'), fn ($query) => $query->where('wp_site_id', (int) $request->input('wp_site_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('synced_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('synced_at', '<=', $request->input('date_to')))
            ->latest('synced_at')
            ->paginate(20)
            ->withQueryString();

        $sites = WPSite::query()
            ->withoutGlobalScope(TenantScope::class)
            ->when($scopeCompanyId, fn ($query) => $query->where('company_id', $scopeCompanyId))
            ->orderBy('site_name')
            ->get(['id', 'site_name']);

        return view('wp-sync-logs.index', compact('logs', 'sites', 'scopeCompanyId'));
    }
}
