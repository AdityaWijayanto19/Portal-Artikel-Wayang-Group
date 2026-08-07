<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $activeCompanyId = $currentUser->isSuperAdmin()
            ? session('active_company_id', 'all')
            : $currentUser->company_id;

        $query = User::with(['roles', 'company', 'companies'])
            ->forTenant($activeCompanyId);

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
        $roles = Role::where('name', '!=', 'super_admin')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {

        $authUser = Auth::user();

        if ($authUser->hasRole('super_admin')) {
            $companies = Company::orderBy('name')->get();
            $roles = Role::where('name', '!=', 'super_admin')->get();
        } else {
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

    public function store(StoreUserRequest $request)
    {
        try {
            $authUser = Auth::user();

            if ($authUser->hasRole('super_admin')) {
                $validated = $request->validated();
                $role = $validated['role'];
                $companyId = $role === 'super_admin' ? null : (int) $validated['company_id'];
            } else {
                $role = 'author';
                $activeCompanyId = session('active_company_id', $authUser->company_id);
                $companyId = (int) (($activeCompanyId && $activeCompanyId !== 'all') ? $activeCompanyId : $authUser->company_id);
            }

            // proses Simpan User
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'username' => strtolower(trim($request->validated('username'))),
                'password' => bcrypt($request->validated('password')),
                'company_id' => $companyId,
            ]);

            // Attach Role & Pivot Company
            $user->assignRole($role);

            if ($companyId) {
                $user->companies()->sync([$companyId]);
            }

            return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
        } catch (\Throwable $th) {
            Log::error('Gagal menambahkan pengguna: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menambahkan pengguna.')
                ->withInput();
        }
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $currentUser = auth()->user();

            if (! $currentUser->isSuperAdmin() && $user->company_id !== $currentUser->company_id) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah user ini.');
            }

            $role = $currentUser->isSuperAdmin()
                ? $request->validated('role')
                : ($user->roles->first()?->name ?? 'author');

            $targetCompanyId = $currentUser->isSuperAdmin()
                ? ($role === 'super_admin' ? null : (int) $request->validated('company_id'))
                : $user->company_id;

            $this->userService->updateUser($user, $request->validated(), $targetCompanyId, $role);

            return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
        } catch (\Throwable $th) {
            Log::error('Gagal memperbarui pengguna: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal memperbarui pengguna.')
                ->withInput();
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $currentUser = auth()->user();

            if ($user->id === $currentUser->id) {
                return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            }

            if (! $currentUser->isSuperAdmin() && $user->company_id !== $currentUser->company_id) {
                abort(403, 'Anda tidak memiliki akses untuk menghapus user ini.');
            }

            $user->delete();

            return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
        } catch (\Throwable $th) {
            Log::error('Gagal menghapus pengguna: ' . $th->getMessage());
            return back()
                ->with('error', 'Terjadi kesalahan sistem. Gagal menghapus pengguna.')
                ->withInput();
        }
    }
}
