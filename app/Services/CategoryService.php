<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data): Category {
            $companyId = $this->resolveCompanyId($data);

            return Category::create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'slug' => $this->normalizeSlug($data['slug'] ?? $data['name']),
            ]);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data): Category {
            $category->update([
                'company_id' => $this->resolveCompanyId($data, $category),
                'name' => $data['name'],
                'slug' => $this->normalizeSlug($data['slug'] ?? $data['name']),
            ]);

            return $category->fresh();
        });
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    protected function resolveCompanyId(array $data = [], ?Category $category = null): int
    {
        $user = auth()->user();
        $requestedCompanyId = $data['company_id'] ?? null;

        if ($user && $user->isSuperAdmin() && $requestedCompanyId) {
            return (int) $requestedCompanyId;
        }

        if ($user && $user->company_id) {
            return (int) $user->company_id;
        }

        if ($category && $category->company_id) {
            return (int) $category->company_id;
        }

        $activeCompanyId = session('active_company_id');

        if ($activeCompanyId && $activeCompanyId !== 'all') {
            return (int) $activeCompanyId;
        }

        throw ValidationException::withMessages([
            'company_id' => 'Tidak dapat menentukan perusahaan aktif untuk kategori ini.',
        ]);
    }

    protected function normalizeSlug(?string $value): string
    {
        return Str::slug($value ?? '');
    }
}
