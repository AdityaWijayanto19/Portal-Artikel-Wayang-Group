<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WPSite;

class WPSitePolicy
{
    /**
     * Pengelolaan WP Site (CRUD) hanya diperuntukkan bagi Superadmin & Admin.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, WPSite $wpSite): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, WPSite $wpSite): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, WPSite $wpSite): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Semua role terautentikasi boleh melihat halaman Visitor Analytics (read-only).
     * Author tidak bisa mengubah flag counter karena create/update tetap dibatasi
     * Superadmin & Admin oleh method CRUD pada policy ini.
     */
    public function viewVisitorAnalytics(User $user): bool
    {
        return true;
    }
}
