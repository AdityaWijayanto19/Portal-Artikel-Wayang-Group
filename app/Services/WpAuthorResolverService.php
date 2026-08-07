<?php

namespace App\Services;

use App\Models\WPSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpAuthorResolverService
{
    /**
     * Resolusi Author ID WordPress berdasarkan data User Laravel (username & email).
     *
     * Logika:
     *  - Cari user WP via `/wp-json/wp/v2/users?search={username|email}` dengan basic auth situs.
     *  - Cocokkan hasil secara EXACT MATCH terhadap `username`, `slug`, atau `email`.
     *  - Ditemukan → kembalikan WP User ID.
     *  - Tidak ditemukan / request error → kembalikan null (atribut 'author' di-omit pada
     *    payload sehingga WordPress memakai akun default/admin tanpa membatalkan publish).
     *
     * Hasil di-cache 7 hari untuk menekan latensi & overhead API.
     */
    public function resolveAuthorId(WPSite $wpSite, string $username, string $email = ''): ?int
    {
        $cleanUsername = strtolower(trim($username));
        $cleanEmail = strtolower(trim($email));
        $cacheKey = "wp_author_id_site_{$wpSite->id}_{$cleanUsername}_{$cleanEmail}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($wpSite, $cleanUsername, $cleanEmail): ?int {
            try {
                $endpoint = rtrim($wpSite->site_url, '/').'/wp-json/wp/v2/users';

                $candidates = [];

                // Search per identifier (username dulu, lalu email) lalu gabung hasilnya.
                foreach (array_filter([$cleanUsername, $cleanEmail]) as $identifier) {
                    $response = Http::withBasicAuth($wpSite->wp_username, $wpSite->appPassword())
                        ->timeout(5)
                        ->get($endpoint, ['search' => $identifier, 'per_page' => 20]);

                    if ($response->successful()) {
                        $candidates = array_merge($candidates, $response->json() ?? []);
                    }
                }

                foreach ($candidates as $user) {
                    $matched = ($cleanUsername !== '' && strtolower((string) ($user['username'] ?? '')) === $cleanUsername)
                        || ($cleanUsername !== '' && strtolower((string) ($user['slug'] ?? '')) === $cleanUsername)
                        || ($cleanEmail !== '' && strtolower((string) ($user['email'] ?? '')) === $cleanEmail);

                    if ($matched && isset($user['id'])) {
                        Log::info("WP Author resolved: '{$cleanUsername}' on '{$wpSite->site_name}' => ID {$user['id']}");

                        return (int) $user['id'];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal resolve WP Author '{$cleanUsername}' pada '{$wpSite->site_name}': ".$e->getMessage());
            }

            // Fallback: null → atribut author di-omit → WP memakai akun default (admin).
            Log::info("WP Author '{$cleanUsername}' tidak ditemukan di '{$wpSite->site_name}' — memakai author default WordPress.");

            return null;
        });
    }

    /**
     * Bersihkan cache author bila username di WP target berubah.
     */
    public function clearAuthorCache(int $siteId, string $username, string $email = ''): void
    {
        Cache::forget("wp_author_id_site_{$siteId}_".strtolower(trim($username)).'_'.strtolower(trim($email)));
    }
}
