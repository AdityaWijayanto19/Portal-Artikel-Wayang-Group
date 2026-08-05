<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Superadmin dapat melihat lintas tenant atau memfilter berdasarkan Session
        if ($user->hasRole('super_admin')) {
            $activeCompanyId = Session::get('active_company_id');

            if ($activeCompanyId && $activeCompanyId !== 'all') {
                $builder->where($model->qualifyColumn('company_id'), (int) $activeCompanyId);
            }

            return;
        }

        // Untuk Admin PIC / Author, paksa hanya membaca data dari company_id milik mereka
        if ($user->company_id) {
            $builder->where($model->qualifyColumn('company_id'), $user->company_id);
        } else {
            // Pengamanan jika user tidak punya company_id dan bukan super_admin
            $builder->whereRaw('1 = 0');
        }
    }
}
