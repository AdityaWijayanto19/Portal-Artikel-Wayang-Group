<?php

namespace App\Http\Controllers;

use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Company;
use App\Services\ActivityLogger;
use App\Services\CategoryService;
use App\Support\ActivityAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): View
    {
        $activeCompanyId = session('active_company_id');

        $categories = Category::query()
            ->with('company')
            ->when($activeCompanyId && $activeCompanyId !== 'all', function ($query) use ($activeCompanyId) {
                $query->where('company_id', $activeCompanyId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('company', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        $companies = Company::query()->orderBy('name')->get();

        return view('categories.create', compact('companies'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            $category = $this->categoryService->create($request->validated());

            $this->activityLogger->log(
                ActivityAction::CATEGORY_CREATED,
                "Menambahkan kategori \"{$category->name}\".",
                subject: $category,
            );

            return redirect()
                ->route('categories.index')
                ->with('success', 'Kategori berhasil ditambahkan.');
        } catch (\Throwable $th) {
            Log::error('Gagal menambahkan kategori: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menambahkan kategori.')
                ->withInput();
        }
    }

    public function edit(Category $category): View
    {
        $companies = Company::query()->orderBy('name')->get();

        return view('categories.edit', compact('category', 'companies'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category = $this->categoryService->update($category, $request->validated());

            $this->activityLogger->log(
                ActivityAction::CATEGORY_UPDATED,
                "Memperbarui kategori \"{$category->name}\".",
                subject: $category,
            );

            return redirect()
                ->route('categories.index')
                ->with('success', 'Kategori berhasil diperbarui.');
        } catch (\Throwable $th) {
            Log::error('Gagal memperbarui kategori: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal memperbarui kategori.')
                ->withInput();
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $categoryName = $category->name;
            $this->categoryService->delete($category);

            $this->activityLogger->log(
                ActivityAction::CATEGORY_DELETED,
                "Menghapus kategori \"{$categoryName}\".",
                subject: $category,
            );

            return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
        } catch (\Throwable $th) {
            Log::error('Gagal menghapus kategori: '.$th->getMessage());

            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menghapus kategori.')
                ->withInput();
        }
    }
}
