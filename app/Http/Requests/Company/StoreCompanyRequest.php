<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:companies,slug'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'primary_color' => ['nullable', 'string', 'max:7', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'sidebar_color' => ['nullable', 'string', 'max:7', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

     public function messages(): array
    {
        return [
            'name.required' => 'Nama perusahaan wajib diisi.',
            'name.string' => 'Nama perusahaan harus berupa teks.',
            'name.max' => 'Nama perusahaan maksimal 255 karakter.',
            'slug.string' => 'Slug perusahaan harus berupa teks.',
            'slug.max' => 'Slug perusahaan maksimal 255 karakter.',
            'slug.unique' => 'Slug perusahaan sudah digunakan.',
            'logo.image' => 'File logo harus berupa gambar.',
            'logo.mimes' => 'Format logo harus JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'primary_color.regex' => 'Format warna primer tidak valid. Gunakan format hex, contoh: #C59B27.',
            'sidebar_color.regex' => 'Format warna sidebar tidak valid. Gunakan format hex, contoh: #1E1E1E.',
            'is_active.boolean' => 'Status aktif perusahaan tidak valid.',
        ];
    }
}
