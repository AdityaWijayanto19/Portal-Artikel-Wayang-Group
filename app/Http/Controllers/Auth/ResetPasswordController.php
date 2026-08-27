<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function showForm(Request $request)
    {
        $token = $request->route('token');

        $resetRecord = DB::table('password_reset_tokens')
            ->where('token', $token)
            ->first();

        if (!$resetRecord) {
            return view('auth.reset-password', ['invalidToken' => true]);
        }

        $createdAt = $resetRecord->created_at;
        $expiresAt = now()->subMinutes(60);

        if ($createdAt < $expiresAt) {
            DB::table('password_reset_tokens')
                ->where('email', $resetRecord->email)
                ->delete();

            return view('auth.reset-password', ['expiredToken' => true]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $resetRecord->email,
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->input('email'))
            ->where('token', $request->input('token'))
            ->first();

        if (!$resetRecord) {
            return redirect()->route('password.request')
                ->with('error', 'Link reset password tidak valid atau sudah digunakan.');
        }

        $createdAt = $resetRecord->created_at;
        $expiresAt = now()->subMinutes(60);

        if ($createdAt < $expiresAt) {
            DB::table('password_reset_tokens')
                ->where('email', $request->input('email'))
                ->delete();

            return redirect()->route('password.request')
                ->with('error', 'Link reset password sudah kadaluarsa. Silakan request ulang.');
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return redirect()->route('password.request')
                ->with('error', 'User tidak ditemukan.');
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->input('email'))
            ->delete();

        return redirect()->route('login')
            ->with('success', 'Password berhasil diubah! Silakan masuk dengan password baru.');
    }
}
