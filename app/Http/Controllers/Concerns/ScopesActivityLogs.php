<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;

/**
 * Resolusi scope perusahaan untuk halaman log (aktivitas user & sinkronisasi WP).
 * Dipakai bersama agar aturan akses konsisten:
 * - super_admin → seluruh perusahaan (null),
 * - admin       → hanya perusahaannya sendiri,
 * - selain itu  → ditolak (403).
 */
trait ScopesActivityLogs
{
    protected function resolveLogScopeCompanyId(User $user): ?int
    {
        if ($user->isSuperAdmin()) {
            return null;
        }

        if ($user->isAdmin()) {
            return $user->primaryCompanyId();
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
