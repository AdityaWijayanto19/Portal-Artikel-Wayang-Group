<?php

namespace App\Support;

/**
 * Daftar aksi activity log terpusat (hindari typo & pencar string di banyak file).
 */
final class ActivityAction
{
    public const AUTH_LOGIN = 'auth.login';

    public const AUTH_LOGOUT = 'auth.logout';

    public const ARTICLE_CREATED = 'article.created';

    public const ARTICLE_UPDATED = 'article.updated';

    public const ARTICLE_DELETED = 'article.deleted';

    public const ARTICLE_SITE_REMOVED = 'article.site_removed';

    public const CATEGORY_CREATED = 'category.created';

    public const CATEGORY_UPDATED = 'category.updated';

    public const CATEGORY_DELETED = 'category.deleted';

    public const WP_SITE_CREATED = 'wp_site.created';

    public const WP_SITE_UPDATED = 'wp_site.updated';

    public const WP_SITE_DELETED = 'wp_site.deleted';

    public const USER_CREATED = 'user.created';

    public const USER_UPDATED = 'user.updated';

    public const USER_DELETED = 'user.deleted';

    public const COMPANY_CREATED = 'company.created';

    public const COMPANY_UPDATED = 'company.updated';

    public const COMPANY_DELETED = 'company.deleted';

    public const LABELS = [
        self::AUTH_LOGIN => 'Login',
        self::AUTH_LOGOUT => 'Logout',
        self::ARTICLE_CREATED => 'Buat Artikel',
        self::ARTICLE_UPDATED => 'Perbarui Artikel',
        self::ARTICLE_DELETED => 'Hapus Artikel',
        self::ARTICLE_SITE_REMOVED => 'Hapus Publikasi Situs',
        self::CATEGORY_CREATED => 'Buat Kategori',
        self::CATEGORY_UPDATED => 'Perbarui Kategori',
        self::CATEGORY_DELETED => 'Hapus Kategori',
        self::WP_SITE_CREATED => 'Tambah WP Site',
        self::WP_SITE_UPDATED => 'Perbarui WP Site',
        self::WP_SITE_DELETED => 'Hapus WP Site',
        self::USER_CREATED => 'Buat Pengguna',
        self::USER_UPDATED => 'Perbarui Pengguna',
        self::USER_DELETED => 'Hapus Pengguna',
        self::COMPANY_CREATED => 'Buat Perusahaan',
        self::COMPANY_UPDATED => 'Perbarui Perusahaan',
        self::COMPANY_DELETED => 'Hapus Perusahaan',
    ];

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? ucwords(str_replace(['.', '_'], [' ', ' '], $action));
    }
}
