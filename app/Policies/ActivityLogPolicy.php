<?php

namespace App\Policies;

use App\Models\User;

/**
 * Otorisasi untuk modul log aktivitas.
 */
class ActivityLogPolicy
{
    /**
     * Halaman log aktivitas hanya untuk Super Admin & Admin.
     */
    public function view(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    /**
     * Detail sensitif (IP & user-agent) hanya boleh dilihat Super Admin.
     *
     * Admin tetap bisa memantau siapa/kapan/apa yang dilakukan sesama admin di
     * perusahaannya (akuntabilitas), tetapi tanpa data yang bisa menelusuri
     * lokasi atau perangkat admin lain.
     */
    public function viewSensitiveDetails(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
