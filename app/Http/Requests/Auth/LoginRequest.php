<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => trim((string) $this->input('login')),
        ]);
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'Email atau Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ];
    }

    /**
     * Autentikasi pintar tingkat Enterprise.
     */
    public function authenticate(): void
    {
        $loginInput = trim((string) $this->input('login'));
        $fieldType  = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // 1. Cari user berdasarkan Email atau Username (case-insensitive)
        $user = User::whereRaw("LOWER({$fieldType}) = LOWER(?)", [$loginInput])->first();

        // JIKA AKUN TIDAK DITEMUKAN: Lempar error global di atas form
        if (! $user) {
            throw ValidationException::withMessages([
                'login_global' => 'Akun tidak terdaftar atau tidak ditemukan dalam sistem.',
            ]);
        }

        // 2. JIKA AKUN ADA TAPI PASSWORD SALAH: Lempar error tepat di field password
        if (! Hash::check($this->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password yang Anda masukkan salah.',
            ]);
        }

        // 3. Login sukses
        Auth::login($user, $this->boolean('remember'));
    }
}
