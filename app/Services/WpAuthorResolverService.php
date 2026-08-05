<?php

namespace App\Services;

use App\Models\WPSite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WpAuthorResolverService
{
    /**
     * Resolusi Author ID WordPress berdasarkan username dasbor.
     * Hasil di-cache 7 hari untuk menekan latensi & overhead API.
     */
    public function resolveAuthorId(WPSite $wpSite, string $username): int
    {
        $cleanUsername = strtolower(trim($username));
        $cacheKey = "wp_author_id_site_{$wpSite->id}_{$cleanUsername}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($wpSite, $cleanUsername) {
            try {
                $endpoint = rtrim($wpSite->site_url, '/').'/wp-json/wp/v2/users';

                $response = Http::withBasicAuth($wpSite->wp_username, $wpSite->wp_app_password)
                    ->timeout(5)
                    ->get($endpoint, ['slug' => $cleanUsername]);

                if ($response->successful() && ! empty($response->json())) {
                    $users = $response->json();

                    if (isset($users[0]['id'])) {
                        Log::info("WP Author resolved: '{$cleanUsername}' on '{$wpSite->site_name}' => ID {$users[0]['id']}");

                        return (int) $users[0]['id'];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal resolve WP Author '{$cleanUsername}' pada '{$wpSite->site_name}': ".$e->getMessage());
            }

            // FALLBACK: gunakan admin default (ID 1) bila username tak ditemukan / API gagal.
            Log::info("Fallback Author ID (1) untuk '{$cleanUsername}' pada '{$wpSite->site_name}'");

            return 1;
        });
    }

    /**
     * Bersihkan cache author bila username di WP target berubah.
     */
    public function clearAuthorCache(int $siteId, string $username): void
    {
        $cleanUsername = strtolower(trim($username));
        Cache::forget("wp_author_id_site_{$siteId}_{$cleanUsername}");
    }
}
