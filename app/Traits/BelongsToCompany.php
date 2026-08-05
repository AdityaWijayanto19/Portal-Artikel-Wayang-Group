<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    /**
     * Boot trait untuk mendaftarkan TenantScope dan Auto-assign company_id.
     */
    protected static function bootBelongsToCompany(): void
    {
        // 1. Filter otomatis query berdasarkan TenantScope
        static::addGlobalScope(new TenantScope);

        // 2. Auto-set company_id saat membuat record baru
        static::creating(function ($model) {
            if (auth()->check() && !auth()->user()->hasRole('super_admin')) {
                if (empty($model->company_id)) {
                    $model->company_id = auth()->user()->company_id;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
