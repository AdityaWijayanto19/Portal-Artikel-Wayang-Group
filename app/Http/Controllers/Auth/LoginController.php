<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
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

        if ($user->hasRole('super_admin')) {
            session([
                'active_company_id' => session('active_company_id', 'all')
            ]);
        } else {
            session([
                'active_company_id' => $user->company_id
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Memproses logout pengguna dan membersihkan sesi.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
