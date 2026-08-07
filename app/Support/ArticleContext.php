<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Konteks perusahaan KHUSUS modul artikel.
 *
 * Sengaja TERPISAH dari scope global tenant (`active_company_id`) yang mengendalikan
 * header, dropdown navigasi, dan TenantScope. Dengan begitu, ketika super admin memilih
 * perusahaan untuk mengelola/menulis artikel, pilihan tersebut HANYA memfokuskan modul
 * artikel — tanpa menggeser scope data perusahaan secara global.
 *
 * Untuk non-super-admin, konteks selalu terkunci pada perusahaan mereka sendiri
 * (session diabaikan) sehingga isolasi tenant tetap terjaga.
 */
class ArticleContext
{
    public const SESSION_KEY = 'article_company_id';

    /**
     * Perusahaan yang sedang difokuskan pada modul artikel (null bila super admin
     * belum memilih).
     */
    public static function companyId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $selected = Session::get(self::SESSION_KEY);

            return is_numeric($selected) ? (int) $selected : null;
        }

        return $user->primaryCompanyId();
    }

    public static function hasCompany(): bool
    {
        return self::companyId() !== null;
    }

    /**
     * Set perusahaan fokus modul artikel (hanya relevan untuk super admin).
     */
    public static function setCompany(int $companyId): void
    {
        Session::put(self::SESSION_KEY, $companyId);
    }

    /**
     * Lupakan fokus perusahaan → super admin kembali ke halaman pemilihan.
     */
    public static function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
