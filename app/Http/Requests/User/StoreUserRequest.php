<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9_\-\.]+$/', // Validasi format slug/username WP
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'company_id' => ['nullable', 'exists:companies,id'], // SuperAdmin wajib pilih, Admin PIC ter-assign otomatis
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username WP hanya boleh berisi huruf, angka, underscore (_), dash (-), dan titik (.). Tanpa spasi!',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'password.min' => 'Password minimal 8 karakter.',
        ];
    }
}
