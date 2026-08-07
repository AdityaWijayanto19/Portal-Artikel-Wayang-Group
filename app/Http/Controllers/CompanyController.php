<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validated();

        // Generasi Slug jika tidak diisi manual
        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        // Handle Upload Logo Perusahaan via ImageService
        if ($request->hasFile('logo')) {
            // Hasilnya akan tersimpan di: uploads/companies/UUID.webp
            $validated['logo_path'] = $this->imageService->processUpload(
                $request->file('logo'),
                'companies'
            );
        }

        Company::create($validated);

        return redirect()->route('companies.index')
            ->with('success', 'Perusahaan baru berhasil ditambahkan.');
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
        $validated = $request->validated();

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active');

        // Handle Update Logo
        if ($request->hasFile('logo')) {
            // 1. Hapus logo lama jika ada lewat ImageService
            if ($company->logo_path) {
                $this->imageService->deleteFile($company->logo_path);
            }

            // 2. Upload logo baru & konversi ke webp
            $validated['logo_path'] = $this->imageService->processUpload(
                $request->file('logo'),
                'companies'
            );
        }

        $company->update($validated);

        return redirect()->route('companies.index')
            ->with('success', "Data perusahaan {$company->name} berhasil diperbarui.");
    }

    /**
     * Hapus perusahaan dari database.
     */
    public function destroy(Company $company): RedirectResponse
    {
        // Hapus logo dari storage jika ada
        if ($company->logo_path) {
            $this->imageService->deleteFile($company->logo_path);
        }

        $companyName = $company->name;
        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', "Perusahaan {$companyName} berhasil dihapus.");
    }
}
