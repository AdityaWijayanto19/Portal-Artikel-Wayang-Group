<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request): View
    {
        $currentUser = auth()->user();

        $query = User::with(['roles', 'company', 'companies']);

        // Scoping tenant
        if (! $currentUser->isSuperAdmin()) {
            $userCompanyId = $currentUser->company_id;
            $query->whereHas('companies', function ($q) use ($userCompanyId) {
                $q->where('companies.id', $userCompanyId);
            })->orWhere('company_id', $userCompanyId);
        } elseif ($request->filled('company_id') && $request->company_id !== 'all') {
            $selectedCompanyId = $request->company_id;
            $query->whereHas('companies', function ($q) use ($selectedCompanyId) {
                $q->where('companies.id', $selectedCompanyId);
            })->orWhere('company_id', $selectedCompanyId);
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }


        $users = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $companies = Company::orderBy('name', 'asc')->get();
        $roles = Role::where('name', '!=', 'super_admin')->get();

        return view('users.index', compact('users', 'companies', 'roles'));
    }

    public function create()
    {
        $authUser = Auth::user();

        if ($authUser->hasRole('super_admin')) {
            // Superadmin: Bebas pilih semua perusahaan & role selain super_admin
            $companies = Company::orderBy('name')->get();
            $roles = Role::where('name', '!=', 'super_admin')->get();
        } else {
            // Admin Tenant: Hanya bisa mendaftarkan ke perusahaannya sendiri & role dikunci 'author'
            // Mengambil perusahaan aktif dari session tenant/auth user
            $companies = Company::where('id', session('active_company_id', $authUser->company_id))->get();
            $roles = Role::where('name', 'author')->get();
        }

        return view('users.create', compact('companies', 'roles'));
    }

    /**
     * Menampilkan form edit user.
     */
    public function edit(User $user)
    {
        $companies = Company::orderBy('name')->get();
        $roles = Role::where('name', '!=', 'super_admin')->get();

        // Eager load relasi agar data terisi di form
        $user->load(['companies', 'roles']);

        return view('users.edit', compact('user', 'companies', 'roles'));
    }

    public function store(Request $request)
    {
        $authUser = Auth    ::user();

        // Validasi Dasar
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($authUser->hasRole('super_admin')) {
            $rules['company_id'] = 'required|exists:companies,id';
            $rules['role'] = 'required|exists:roles,name';
        } else {
            // memaksa company_id & role jika diinput oleh Admin
            $request->merge([
                'company_id' => session('active_company_id', $authUser->company_id),
                'role' => 'author',
            ]);

            $rules['company_id'] = 'required|exists:companies,id';
            $rules['role'] = 'required|in:author';
        }

        $validated = $request->validate($rules);

        // proses Simpan User
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'company_id' => $validated['company_id'], // jika pakai single tenancy di user
        ]);

        // Attach Role & Pivot Company
        $user->assignRole($validated['role']);
        $user->companies()->sync([$validated['company_id']]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        // Security check: Admin PIC tidak boleh update user luar tenant
        if (! $currentUser->isSuperAdmin() && $user->company_id !== $currentUser->company_id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah user ini.');
        }

        $targetCompanyId = $currentUser->isSuperAdmin()
            ? $request->validated('company_id')
            : $user->company_id;

        $this->userService->updateUser($user, $request->validated(), $targetCompanyId);

        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (! $currentUser->isSuperAdmin() && $user->company_id !== $currentUser->company_id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus user ini.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
