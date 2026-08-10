@php
    use Illuminate\Support\Facades\Storage;

    $currentUser = auth()->user();
    $userRole = str_replace('_', ' ', $currentUser?->roles->first()?->name ?? 'admin');
    $isSuperAdmin = $currentUser?->isSuperAdmin() ?? false;
    $activeCompany = $activeCompany ?? null;

    $isTenantBrand = !$isSuperAdmin && $activeCompany;
    $brandName = $isTenantBrand ? $activeCompany->name : 'Wayang Group';
    $brandTagline = 'Portal Artikel';
@endphp

<div
    class="h-full flex flex-col justify-between p-5 select-none font-sans antialiased bg-brand-sidebar text-brand-sidebar-text">

    <div class="space-y-6">

        <div class="flex items-center gap-3 pb-4 border-b border-white/10">

            {{-- 1. Bagian LOGO / ICON --}}
            @if ($isSuperAdmin || $activeCompany?->logo_path)
                <div class="w-10 h-10 flex items-center justify-center shrink-0">
                    <img src="{{ $isSuperAdmin ? asset('images/logo.png') : Storage::url($activeCompany->logo_path) }}"
                        alt="{{ $brandName }}" class="max-h-full max-w-full object-contain">
                </div>
            @elseif ($activeCompany)
                <div
                    class="w-10 h-10 rounded-lg bg-brand flex items-center justify-center text-sm font-bold text-brand-text shrink-0">
                    {{ strtoupper(substr($activeCompany->name, 0, 2)) }}
                </div>
            @else
                <div
                    class="w-10 h-10 rounded-lg bg-brand flex items-center justify-center text-sm font-bold text-brand-text shrink-0">
                    WG
                </div>
            @endif

            {{-- 2. Bagian TEKS BRAND (Selalu Muncul) --}}
            <div class="space-y-0.5 min-w-0">
                <span
                    class="text-[10px] font-black uppercase tracking-[0.2em] {{ $isSuperAdmin ? 'text-brand' : 'text-brand-sidebar-text' }} truncate block">
                    {{ $brandName }}
                </span>
                <h1 class="text-xs font-bold leading-tight text-brand-sidebar-text/80 font-serif truncate">
                    {{ $brandTagline }}
                </h1>
            </div>

        </div>

        @hasrole('super_admin')
            <!-- Tenant Switcher Dropdown (Pergantian Perusahaan) -->
            <div class="space-y-1.5">
                <form action="{{ route('tenant.switch') }}" method="POST" id="tenantSwitchForm" onchange="this.submit()">
                    @csrf
                    @php
                        $tenantOptions = [['id' => 'all', 'name' => 'Semua Perusahaan']];
                        foreach ($globalCompanies as $company) {
                            $tenantOptions[] = ['id' => $company->id, 'name' => $company->name];
                        }
                    @endphp

                    <x-select name="company_id" label="Perusahaan Aktif" theme="dark" :value="session('active_company_id', 'all')" :options="$tenantOptions"
                        :searchable="true" placeholder="Pilih perusahaan..." :showError="false" />
                </form>
            </div>
        @endhasrole

        <!-- Navigasi Menu -->
        <nav class="space-y-1.5 text-xs font-medium">

            <!-- Dashboard -->
            <a href="{{ Route::has('dashboard') ? route('dashboard') : 'javascript:void(0)' }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 00-1 1m-6 0h6" />
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Perusahaan / Companies (CRUD Tenant) -->
            @hasrole('super_admin')
                <a href="{{ Route::has('companies.index') ? route('companies.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('companies.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H7m4 0v10" />
                    </svg>
                    <span>Manajemen Perusahaan</span>
                </a>
            @endhasrole

            <!-- SEO Article Editor -->
            <a href="{{ Route::has('articles.index') ? route('articles.index') : 'javascript:void(0)' }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('articles.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                <svg class="w-4 h-4 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <span>Manajemen Artikel</span>
            </a>

            @hasrole('super_admin|admin')
                <a href="{{ Route::has('users.index') ? route('users.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('users.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users-icon lucide-users">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <path d="M16 3.128a4 4 0 0 1 0 7.744" />
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                    <span>Manajemen Pengguna</span>
                </a>
                <a href="{{ Route::has('categories.index') ? route('categories.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('categories.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-folder-tree-icon lucide-folder-tree">
                        <path
                            d="M20 10a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2.5a1 1 0 0 1-.8-.4l-.9-1.2A1 1 0 0 0 15 3h-2a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                        <path
                            d="M20 21a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1h-2.9a1 1 0 0 1-.88-.55l-.42-.85a1 1 0 0 0-.92-.6H13a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1Z" />
                        <path d="M3 5a2 2 0 0 0 2 2h3" />
                        <path d="M3 3v13a2 2 0 0 0 2 2h3" />
                    </svg>
                    <span>Manajemen Kategori</span>
                </a>
                <a href="{{ Route::has('wp-sites.index') ? route('wp-sites.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('wp-sites.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-globe-icon lucide-globe">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                        <path d="M2 12h20" />
                    </svg>
                    <span>Manajemen WP-Sites</span>
                </a>


                <a href="{{ Route::has('wp-sync-logs.index') ? route('wp-sync-logs.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('wp-sync-logs.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-refresh-cw-icon lucide-refresh-cw">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                        <path d="M21 3v5h-5" />
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                        <path d="M8 16H3v5" />
                    </svg>
                    <span>Log Posting Artikel WP</span>
                </a>

                <a href="{{ Route::has('activity-logs.index') ? route('activity-logs.index') : 'javascript:void(0)' }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('activity-logs.*') ? 'bg-brand text-brand-text font-bold shadow-sm' : 'text-brand-sidebar-text/70 hover:bg-white/10 hover:text-brand-sidebar-text' }}">
                    <svg class="w-4 h-4 opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-history-icon lucide-history">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                        <path d="M3 3v5h5" />
                        <path d="M12 7v5l4 2" />
                    </svg>
                    <span>Log Aktivitas</span>
                </a>
            @endhasrole
        </nav>
    </div>
</div>
