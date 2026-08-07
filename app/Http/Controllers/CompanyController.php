<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyController extends Controller
{
    // 2. Inject ImageService via Constructor
    public function __construct(
        protected ImageService $imageService
    ) {}

    /**
     * Tampilkan daftar seluruh perusahaan.
     */
    public function index(Request $request): View
    {
        $companies = Company::query()
            ->withCount(['users', 'articles', 'categories', 'wpSites'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('companies.index', compact('companies'));
    }

    /**
     * Tampilkan form tambah perusahaan.
     */
    public function create(): View
    {
        return view('companies.create');
    }

    /**
     * Simpan perusahaan baru ke database.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
            $validated['is_active'] = $request->has('is_active');

            if ($request->hasFile('logo')) {
                $validated['logo_path'] = $this->imageService->processUpload(
                    $request->file('logo'),
                    'companies'
                );
            }

            Company::create($validated);

            return redirect()->route('companies.index')
                ->with('success', 'Perusahaan baru berhasil ditambahkan.');
        } catch (\Throwable $th) {
            Log::error('Gagal menambahkan perusahaan: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menambahkan perusahaan.')
                ->withInput();
        }
    }

    /**
     * Tampilkan detail perusahaan beserta statistik ringkas.
     */
    public function show(Company $company): View
    {
        $company->loadCount(['users', 'articles', 'categories', 'wpSites']);
        $latestUsers = $company->users()->latest()->limit(5)->get();

        return view('companies.show', compact('company', 'latestUsers'));
    }

    /**
     * Tampilkan form edit perusahaan.
     */
    public function edit(Company $company): View
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Update data perusahaan.
     */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        try {
            $validated = $request->validated();

            $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
            $validated['is_active'] = $request->has('is_active');

            if ($request->hasFile('logo')) {
                if ($company->logo_path) {
                    $this->imageService->deleteFile($company->logo_path);
                }

                $validated['logo_path'] = $this->imageService->processUpload(
                    $request->file('logo'),
                    'companies'
                );
            }

            $company->update($validated);

            return redirect()->route('companies.index')
                ->with('success', "Data perusahaan {$company->name} berhasil diperbarui.");
        } catch (\Throwable $th) {
            Log::error('Gagal memperbarui perusahaan: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal memperbarui perusahaan.')
                ->withInput();
        }
    }

    /**
     * Hapus perusahaan dari database.
     */
    public function destroy(Company $company): RedirectResponse
    {
        try {
            if ($company->logo_path) {
                $this->imageService->deleteFile($company->logo_path);
            }

            $companyName = $company->name;
            $company->delete();

            return redirect()->route('companies.index')
                ->with('success', "Perusahaan {$companyName} berhasil dihapus.");
        } catch (\Throwable $th) {
            Log::error('Gagal menghapus perusahaan: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menghapus perusahaan.')
                ->withInput();
        }
    }
}
