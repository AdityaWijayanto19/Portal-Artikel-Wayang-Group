<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesActivityLogs;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Support\ActivityAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    use ScopesActivityLogs;

    /**
     * Daftar log aktivitas pengguna. Super admin melihat semua perusahaan
     * (bisa difilter), admin hanya perusahaannya sendiri.
     */
    public function index(Request $request): View
    {
        $scopeCompanyId = $this->resolveLogScopeCompanyId($request->user());

        $logs = ActivityLog::query()
            ->with(['user.roles', 'company'])
            ->when($scopeCompanyId, fn ($query) => $query->where('company_id', $scopeCompanyId))
            ->when($request->filled('company_id') && $scopeCompanyId === null, function ($query) use ($request) {
                $query->where('company_id', (int) $request->input('company_id'));
            })
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', (int) $request->input('user_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $companies = $scopeCompanyId === null
            ? Company::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $users = User::query()
            ->forTenant($scopeCompanyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $actions = ActivityAction::LABELS;

        return view('activity-logs.index', compact('logs', 'companies', 'users', 'actions', 'scopeCompanyId'));
    }
}
