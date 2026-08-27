<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsNotThrottled
{
    public function handle(Request $request, Closure $next, string $maxAttempts = '5', string $decayMinutes = '5'): Response
    {
        $max = (int) $maxAttempts;
        $decay = (int) $decayMinutes;
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $retryAfter = RateLimiter::availableIn($key);

            $minutes = (int) ceil($retryAfter / 60);
            $timeText = $minutes > 1 ? "{$minutes} menit" : "{$retryAfter} detik";

            $routeName = $request->route()?->getName();

            if ($routeName === 'password.update') {
                $token = $request->input('token') ?? $request->route('token');
                $redirectUrl = $token ? route('password.reset', $token) : route('password.request');
            } else {
                $redirectUrl = route('password.request');
            }

            return redirect($redirectUrl)
                ->with('block', "Terlalu banyak request. Silakan coba lagi dalam {$timeText}.")
                ->withInput($request->only('email'));
        }

        RateLimiter::hit($key, $decay * 60);

        return $next($request);
    }

    private function throttleKey(Request $request): string
    {
        $email = trim(strtolower((string) $request->input('email', '')));
        $routeName = $request->route()?->getName();

        return "{$routeName}|{$email}|{$request->ip()}";
    }
}
