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
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                'unique:users,username',
                'regex:/^[a-zA-Z0-9 ]+$/',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                'string',
                'exists:roles,name',
            ],

            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],
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
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username maksimal 100 karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, dan spasi.',
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
            'role.required' => 'Role wajib dipilih.',
            'role.string' => 'Role yang dipilih tidak valid.',
            'role.exists' => 'Role yang dipilih tidak tersedia.',
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'company_id.integer' => 'Perusahaan yang dipilih tidak valid.',
        ];
    }
}
