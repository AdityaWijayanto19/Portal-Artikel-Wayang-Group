<?php

namespace App\Http\Requests\WpSite;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWpSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wp_site')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('flag_counter_url') && ! $this->filled('flag_counter_url')) {
            $this->merge([
                'flag_counter_url' => null,
            ]);
        }

        if (
            $this->user() &&
            ! $this->user()->isSuperAdmin() &&
            ! $this->filled('company_id')
        ) {
            $this->merge([
                'company_id' => $this->user()->company_id,
            ]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();

        $companyId = $this->input('company_id')
            ?? $user?->company_id
            ?? session('active_company_id');

        $site = $this->route('wp_site');

        return [
            'company_id' => [
                Rule::requiredIf($user?->isSuperAdmin()),
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'site_name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('wp_sites', 'site_name')
                    ->where(
                        fn ($query) => $query->where('company_id', $companyId)
                    )
                    ->ignore($site->id),
            ],

            'site_url' => [
                'required',
                'url',
                'max:255',

                Rule::unique('wp_sites', 'site_url')
                    ->where(
                        fn ($query) => $query->where('company_id', $companyId)
                    )
                    ->ignore($site->id),
            ],

            'wp_username' => [
                'required',
                'string',
                'max:255',
            ],

            'wp_app_password' => [
                'required',
                'string',
                'max:255',
            ],

            'flag_counter_url' => [
                'nullable',
                'url',
                'max:2000',
            ],

            'category_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'category_ids.*' => [
                'integer',

                Rule::exists('categories', 'id')
                    ->where(
                        fn ($query) => $query->where('company_id', $companyId)
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.integer' => 'Perusahaan yang dipilih tidak valid.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak ditemukan.',
            'site_name.required' => 'Nama WP Site wajib diisi.',
            'site_name.string' => 'Nama WP Site harus berupa teks.',
            'site_name.max' => 'Nama WP Site maksimal 255 karakter.',
            'site_name.unique' => 'Nama WP Site sudah digunakan pada perusahaan ini.',
            'site_url.required' => 'URL website wajib diisi.',
            'site_url.url' => 'URL website harus berupa URL yang valid.',
            'site_url.max' => 'URL website maksimal 255 karakter.',
            'site_url.unique' => 'URL website sudah terdaftar pada perusahaan ini.',
            'wp_username.required' => 'Username WordPress wajib diisi.',
            'wp_username.string' => 'Username WordPress harus berupa teks.',
            'wp_username.max' => 'Username WordPress maksimal 255 karakter.',
            'wp_app_password.required' => 'Application Password WordPress wajib diisi.',
            'wp_app_password.string' => 'Application Password WordPress harus berupa teks.',
            'wp_app_password.max' => 'Application Password WordPress maksimal 255 karakter.',
            'flag_counter_url.url' => 'Flag Counter URL harus berupa URL yang valid.',
            'flag_counter_url.max' => 'Flag Counter URL maksimal 2000 karakter.',
            'category_ids.required' => 'Kategori wajib dipilih.',
            'category_ids.array' => 'Format kategori tidak valid.',
            'category_ids.min' => 'Pilih minimal satu kategori.',
            'category_ids.*.integer' => 'Kategori yang dipilih tidak valid.',
            'category_ids.*.exists' => 'Kategori yang dipilih tidak ditemukan.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $companyId = $this->input('company_id')
                ?? $this->user()?->company_id
                ?? session('active_company_id');

            $categoryIds = array_values(
                array_filter(
                    (array) $this->input('category_ids', [])
                )
            );

            if (! $companyId) {
                $validator->errors()->add(
                    'company_id',
                    'Tidak dapat menentukan perusahaan aktif untuk kategori WP Site ini.'
                );

                return;
            }

            $categoryExistsForCompany = Category::query()
                ->whereIn('id', $categoryIds)
                ->where('company_id', $companyId)
                ->count();

            if ($categoryExistsForCompany !== count($categoryIds)) {
                $validator->errors()->add(
                    'category_ids',
                    'Semua kategori harus sesuai dengan perusahaan yang dipilih.'
                );
            }
        });
    }
}
