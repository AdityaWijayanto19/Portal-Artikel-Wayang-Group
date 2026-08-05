<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{ 
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // 1. Dapatkan daftar perusahaan yang boleh diakses pengguna ini
        $availableCompanies = $user->hasRole('super_admin')
            ? Company::orderBy('name')->get()
            : Company::where('id', $user->company_id)->get();

        // 2. Tentukan active_company_id dari Session
        $activeCompanyId = session('active_company_id');

        if (is_null($activeCompanyId)) {
            if ($user->hasRole('super_admin')) {
                $activeCompanyId = 'all';
            } else {
                $activeCompanyId = $user->company_id;
            }

            session(['active_company_id' => $activeCompanyId]);
        }

        // 3. Validasi Keamanan Hak Akses Tenant (Non-Superadmin terkunci ke company_id milik mereka)
        if (! $user->hasRole('super_admin') && $activeCompanyId !== 'all') {
            if ((int) $activeCompanyId !== (int) $user->company_id) {
                $activeCompanyId = $user->company_id;
                session(['active_company_id' => $activeCompanyId]);
            }
        }

        // 4. Ambil Objek Perusahaan Aktif
        $currentCompany = null;
        if ($activeCompanyId && $activeCompanyId !== 'all') {
            $currentCompany = Company::find($activeCompanyId);
        }

        // 5. Share Variabel Global ke Seluruh Blade Views
        View::share('activeCompanyId', $activeCompanyId);
        View::share('currentCompany', $currentCompany);
        View::share('availableCompanies', $availableCompanies);

        return $next($request);
    }
}
