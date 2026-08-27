<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginIsNotThrottled
{
    private const WARNING_THRESHOLD = 3;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $retryAfter = RateLimiter::availableIn($key);

            $minutes = (int) ceil($retryAfter / 60);
            $timeText = $minutes > 1 ? "{$minutes} menit" : "{$retryAfter} detik";

            return redirect()->route('login')
                ->with('block', "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$timeText}.")
                ->withInput($request->only('login'));
        }

        $remaining = 10 - RateLimiter::attempts($key);

        if ($remaining <= self::WARNING_THRESHOLD && $remaining > 0) {
            RateLimiter::hit($key, 60);

            $warning = "Anda tersisa {$remaining} percobaan lagi. "
                . "Jika lupa password, gunakan fitur ";
            $warning .= '<a href="' . route('password.request') . '" class="font-semibold underline">Lupa Password</a>';
            $warning .= ' atau hubungi Superadmin.';

            return redirect()->back()
                ->with('warning', $warning)
                ->withInput($request->only('login'));
        }

        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            $retryAfter = $response->headers->get('Retry-After', 60);
            $minutes = (int) ceil((int) $retryAfter / 60);
            $timeText = $minutes > 1 ? "{$minutes} menit" : "{$retryAfter} detik";

            return redirect()->route('login')
                ->with('block', "Terlalu banyak percobaan gagal. Silakan coba lagi dalam {$timeText}.")
                ->withInput($request->only('login'));
        }

        return $response;
    }

    private function throttleKey(Request $request): string
    {
        $login = trim(strtolower((string) $request->input('login', '')));

        return 'login|' . $login . '|' . $request->ip();
    }
}
