<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * Menampilkan form login.
     * Jika user sudah terautentikasi, otomatis diarahkan ke dashboard sesuai role.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        return view('auth.login');
    }

    /**
     * Memproses autentikasi pintar pengguna via LoginRequest.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        $this->activityLogger->log(
            ActivityAction::AUTH_LOGIN,
            "{$user->name} berhasil login ke sistem.",
            user: $user,
            companyId: $user->company_id,
        );

        if ($user->hasRole('super_admin')) {
            session([
                'active_company_id' => session('active_company_id', 'all'),
            ]);
        } else {
            session([
                'active_company_id' => $user->company_id,
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Memproses logout pengguna dan membersihkan sesi.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->activityLogger->log(
                ActivityAction::AUTH_LOGOUT,
                "{$user->name} keluar dari sistem.",
                user: $user,
                companyId: $user->company_id,
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
