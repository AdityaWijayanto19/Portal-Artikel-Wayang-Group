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
        if ($request->user()) {
            return redirect()->route('dashboard', [
                'panel' => $this->dashboardPanelFor($request->user()),
            ]);
        }

        return view('auth.login');
    }

    /**
     * Memproses autentikasi pintar pengguna via LoginRequest.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        // 1. Jalankan autentikasi bertahap (Cek Akun -> Cek Password)
        $request->authenticate();

        // 2. Regenerasi sesi untuk keamanan (mencegah Session Fixation)
        $request->session()->regenerate();

        $user = $request->user();

        // 3. Set Context Tenant awal ke Session berdasarkan Role Spatie
        if ($user->hasRole('super_admin')) {
            // Superadmin secara default melihat semua tenant
            session(['active_company_id' => session('active_company_id', 'all')]);
        } else {
            // Admin PIC & Author dikunci langsung ke company_id milik mereka
            session(['active_company_id' => $user->company_id]);
        }

        // 4. Redirect ke rute yang dituju (intended) atau ke panel dashboard relevan
        return redirect()->intended(route('dashboard', [
            'panel' => $this->dashboardPanelFor($user),
        ]));
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

    /**
     * Penentuan nama panel dashboard berdasarkan Spatie Role.
     */
    private function dashboardPanelFor(?User $user): string
    {
        if (! $user) {
            return 'overview';
        }

        return match (true) {
            $user->hasRole('super_admin') => 'overview',
            $user->hasRole('admin')       => 'holding',
            $user->hasRole('author')      => 'editorial',
            default                       => 'overview',
        };
    }
}
