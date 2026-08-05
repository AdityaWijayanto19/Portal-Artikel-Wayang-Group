<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Memproses pergantian perusahaan (Tenant Switching).
     */
    public function switchTenant(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => ['required', 'string'],
        ]);

        $user = $request->user();
        $targetCompanyId = $request->input('company_id');

        // 1. Opsi memilih 'all' (Tampilan lintas seluruh anak perusahaan)
        if ($targetCompanyId === 'all') {
            if (! $user->hasRole('super_admin')) {
                abort(403, 'Aksi ini hanya diizinkan untuk Super Admin.');
            }

            session(['active_company_id' => 'all']);

            return redirect()->back()->with('success', 'Berhasil beralih ke Tampilan Semua Perusahaan.');
        }

        // 2. Opsi memilih perusahaan spesifik
        $company = Company::findOrFail($targetCompanyId);

        // Otorisasi Akses Tenant
        if (! $user->hasRole('super_admin')) {
            $hasAccess = $user->companies->contains('id', $company->id);

            if (! $hasAccess) {
                abort(403, 'Anda tidak memiliki akses ke perusahaan ini.');
            }
        }

        // Simpan tenant aktif ke Session
        session(['active_company_id' => $company->id]);

        return redirect()->back()->with('success', "Berhasil beralih ke {$company->name}.");
    }
}
