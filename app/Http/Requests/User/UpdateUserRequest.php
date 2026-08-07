<?php

namespace App\Http\Requests\User;

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
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ];
    }

    /**
     * Aturan "inputan cerdas": Super Admin = holding Wayang Group (tanpa perusahaan tenant),
     * sedangkan Admin/Author wajib memiliki perusahaan tenant nyata.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $role = $this->input('role');
            $companyId = $this->input('company_id');

            if (in_array($role, ['admin', 'author'], true) && empty($companyId)) {
                $validator->errors()->add('company_id', 'Perusahaan wajib dipilih untuk role admin/author.');
            }

            if ($role === 'super_admin' && ! empty($companyId)) {
                $validator->errors()->add('company_id', 'Super Admin hanya milik perusahaan Wayang Group.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username WP hanya boleh berisi huruf, angka, underscore (_), dash (-), dan titik (.). Tanpa spasi!',
        ];
    }
}
