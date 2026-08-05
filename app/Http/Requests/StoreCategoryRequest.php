<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            ?? ($user?->company_id ?? session('active_company_id'));

        return [
            'company_id' => [
                Rule::requiredIf($user?->isSuperAdmin()),
                'nullable',
                'integer',
                'exists:companies,id',
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
        ];
    }
}
