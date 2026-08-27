<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->input('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Format email tidak valid.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Email tidak terdaftar di sistem. Jika Anda yakin sudah terdaftar, silakan hubungi Superadmin.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        Mail::to($email)->queue(new ResetPasswordMail($user, $token));

        return redirect()->route('password.email-sent')
            ->with('email_address', $email);
    }

    public function emailSent()
    {
        $emailAddress = session('email_address');

        if (!$emailAddress) {
            return redirect()->route('password.request');
        }

        return view('auth.email-sent', ['emailAddress' => $emailAddress]);
    }
}
