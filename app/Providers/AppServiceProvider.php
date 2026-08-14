<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gate Super Admin
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            try {
                return $user->hasPermissionTo($ability) ? true : null;
            } catch (PermissionDoesNotExist) {
                // Permission belum di-seed → biarkan Policy yang memutuskan.
                return null;
            }
        });

        // Inject $globalCompanies ke navigation (sidebar) DAN header
        View::composer(['partials.navigation', 'partials.header'], function ($view) {
            $user = Auth::user();

            if (! $user) {
                $companies = collect();
            } elseif ($user->isSuperAdmin()) {
                // Super Admin dapet semua perusahaan
                $companies = Company::orderBy('name', 'asc')->get();
            } else {
                // User biasa/admin dapet perusahaan tempat dia terdaftar
                $companies = $user->companies ?? collect();
            }

            $view->with('globalCompanies', $companies);
        });
    }
}
