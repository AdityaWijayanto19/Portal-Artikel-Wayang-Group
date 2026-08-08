<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->input('slug')) && $this->filled('name')) {
            $this->merge([
                'slug' => Str::slug($this->input('name')),
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

        $category = $this->route('category');

        $companyId = $this->input('company_id')
            ?? ($user?->company_id ?? session('active_company_id'));

        return [
            'company_id' => [
                Rule::requiredIf($user?->isSuperAdmin()),
                'nullable',
                'integer',
                'exists:companies,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',

                Rule::unique('categories', 'slug')
                    ->where(
                        fn ($query) =>
                            $query->where('company_id', $companyId)
                    )
                    ->ignore($category->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.exists' => 'Perusahaan yang dipilih tidak valid.',
            'company_id.integer' => 'Perusahaan yang dipilih tidak valid.',
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'slug.string' => 'Slug harus berupa teks.',
            'slug.max' => 'Slug maksimal 255 karakter.',
            'slug.unique' => 'Slug kategori sudah digunakan pada perusahaan ini.',
        ];
    }
}
