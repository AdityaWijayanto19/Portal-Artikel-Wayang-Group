<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:100',
                'unique:users,username,' . $userId,
                'regex:/^[a-zA-Z0-9_\-\.]+$/',
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username WP hanya boleh berisi huruf, angka, underscore (_), dash (-), dan titik (.). Tanpa spasi!',
        ];
    }
}
