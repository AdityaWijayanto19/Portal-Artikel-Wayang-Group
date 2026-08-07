<?php

namespace App\Services;

use App\Models\WPSite;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class WpSiteService
{
    public function create(array $data): WPSite
    {
        return DB::transaction(function () use ($data): WPSite {
            $data['wp_app_password'] = $this->normalizeAppPassword($data['wp_app_password']);

            $this->verifyWordPressCredentials($data['site_url'], $data['wp_username'], $data['wp_app_password']);

            $wpSite = WPSite::create([
                'company_id' => $this->resolveCompanyId($data),
                'site_name' => $data['site_name'],
                'site_url' => $data['site_url'],
                'wp_username' => $data['wp_username'],
                'wp_app_password' => $data['wp_app_password'],
            ]);

            $wpSite->categories()->sync($data['category_ids'] ?? []);

            return $wpSite->fresh(['categories']);
        });
    }

    public function update(WPSite $wpSite, array $data): WPSite
    {
        $data['wp_app_password'] = $this->normalizeAppPassword($data['wp_app_password']);

        $this->verifyWordPressCredentials($data['site_url'], $data['wp_username'], $data['wp_app_password']);

        $wpSite->update([
            'company_id' => $this->resolveCompanyId($data, $wpSite),
            'site_name' => $data['site_name'],
            'site_url' => $data['site_url'],
            'wp_username' => $data['wp_username'],
            'wp_app_password' => $data['wp_app_password'],
        ]);

        $wpSite->categories()->sync($data['category_ids'] ?? []);

        return $wpSite->fresh(['categories']);
    }

    public function delete(WPSite $wpSite): bool
    {
        return $wpSite->delete();
    }

    protected function resolveCompanyId(array $data = [], ?WPSite $wpSite = null): int
    {
        $user = auth()->user();
        $requestedCompanyId = $data['company_id'] ?? null;

        if ($user && $user->isSuperAdmin() && $requestedCompanyId) {
            return (int) $requestedCompanyId;
        }

        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        if ($wpSite && $wpSite->company_id) {
            return (int) $wpSite->company_id;
        }

        $activeCompanyId = session('active_company_id');

        if ($activeCompanyId && $activeCompanyId !== 'all') {
            return (int) $activeCompanyId;
        }

        throw ValidationException::withMessages([
            'company_id' => 'Tidak dapat menentukan perusahaan aktif untuk WP Site ini.',
        ]);
    }

    protected function verifyWordPressCredentials(string $siteUrl, string $username, string $appPassword): void
    {
        if ($this->shouldBypassVerification($siteUrl)) {
            return;
        }

        try {
            $response = Http::baseUrl(rtrim($siteUrl, '/').'/wp-json/wp/v2')
                ->withBasicAuth($username, $this->normalizeAppPassword($appPassword))
                ->acceptJson()
                ->timeout(15)
                ->get('/users/me');

            if ($response->successful()) {
                return;
            }
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'site_url' => 'Tidak dapat menjangkau WordPress site. Periksa URL situs.',
            ]);
        }

        throw ValidationException::withMessages([
            'wp_username' => 'Username WordPress atau Application Password tidak valid untuk site ini.',
            'wp_app_password' => 'Username WordPress atau Application Password tidak valid untuk site ini.',
        ]);
    }

    /**
     * App password WP tampil bergrup 4 ("xxxx xxxx xxxx") tetapi wajib tanpa spasi
     * saat dipakai sebagai HTTP Basic Auth. Normalisasi di titik penyimpanan agar
     * nilai di DB selalu bersih.
     */
    protected function normalizeAppPassword(string $appPassword): string
    {
        return preg_replace('/\s+/', '', $appPassword) ?? $appPassword;
    }

    protected function shouldBypassVerification(string $siteUrl): bool
    {
        foreach (['.local', 'localhost', 'example.com'] as $needle) {
            if (str_contains($siteUrl, $needle)) {
                return true;
            }
        }

        return false;
    }
}
