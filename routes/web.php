<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WpSiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes (Belum Login)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Sudah Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'role:super_admin'])->group(function () {
        Route::resource('companies', CompanyController::class);
    });

    Route::middleware(['auth', 'role:super_admin|admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('wp-sites', WpSiteController::class);
    });

    // Protected Routes berdasarkan Spatie Roles
    Route::middleware('role:super_admin|admin|author')->group(function () {
        // Dashboard Landing
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Multi-Tenant Switcher
        Route::post('/tenant/switch', [TenantController::class, 'switchTenant'])->name('tenant.switch');

        // Article & WordPress Publishing Management
        Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/articles/select', [ArticleController::class, 'selectCompany'])->name('articles.select');
        Route::get('/articles/company/{company}', [ArticleController::class, 'chooseCompany'])->name('articles.company');
        Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
        Route::get('/articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
        Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
        Route::put('/articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::post('/articles/{article}/publish', [ArticleController::class, 'publish'])->name('articles.publish');
        Route::post('/articles/{article}/retry', [ArticleController::class, 'retry'])->name('articles.retry');
    });
});
