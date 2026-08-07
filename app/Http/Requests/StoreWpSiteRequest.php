<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWpSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user() && ! $this->user()->isSuperAdmin() && ! $this->filled('company_id')) {
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
        $siteId = $this->route('wp_site')?->id;

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
                    ->ignore($siteId)
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'site_url' => [
                'required',
                'url',
                'max:255',
                Rule::unique('wp_sites', 'site_url')
                    ->ignore($siteId)
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'wp_username' => ['required', 'string', 'max:255'],
            'wp_app_password' => ['required', 'string', 'max:255'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $companyId = $this->input('company_id')
                ?? $this->user()?->company_id
                ?? session('active_company_id');

            $categoryIds = array_values(array_filter((array) $this->input('category_ids', [])));

            if (empty($categoryIds)) {
                $validator->errors()->add('category_ids', 'Pilih minimal satu kategori untuk WP Site ini.');
                return;
            }

            if (! $companyId) {
                $validator->errors()->add('company_id', 'Tidak dapat menentukan perusahaan aktif untuk kategori WP Site ini.');
                return;
            }

            $categoryExistsForCompany = Category::query()
                ->whereIn('id', $categoryIds)
                ->where('company_id', $companyId)
                ->count();

            if ($categoryExistsForCompany !== count($categoryIds)) {
                $validator->errors()->add('category_ids', 'Semua kategori harus sesuai dengan perusahaan yang dipilih.');
            }
        });
    }
}
