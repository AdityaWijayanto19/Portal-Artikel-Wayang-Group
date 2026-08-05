@php
    use Illuminate\Support\Facades\Storage;

    $currentUser = $currentUser ?? auth()->user();
    $userRole = str_replace('_', ' ', $currentUser->roles->first()?->name ?? 'admin pic');
    $userName = $currentUser->name ?? 'Yunnappie';
    $userAvatar =
        $currentUser->profile_photo_url ??
        'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&color=C59B27&background=2A2A2A';

    // Perbaikan: Pastikan $globalCompanies selalu ada
    $companiesList = $globalCompanies ?? collect();
    $activeCompanyId = session('active_company_id');
    $activeCompany = null;

    if ($currentUser->isSuperAdmin()) {
        if ($activeCompanyId && $activeCompanyId !== 'all') {
            $activeCompany = $companiesList->firstWhere('id', $activeCompanyId);
        }
    } else {
        // Perbaikan: Menggunakan $currentUser->company (BelongsTo) bukan $currentUser->companies (null)
        $activeCompany = $currentUser->company;
    }

    // Assign Nama Perusahaan
    $displayCompanyName = $activeCompany->name ?? 'Wayang Group';

    // Ambil kolom logo (Mengecek beberapa nama kolom opsional di DB)
    $rawLogo = $activeCompany?->logo_url ?? ($activeCompany?->logo ?? $activeCompany?->logo_path);

    // Evaluasi URL Logo
    if ($rawLogo) {
        if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://')) {
            $displayCompanyLogo = $rawLogo;
        } else {
            $displayCompanyLogo = Storage::url($rawLogo);
        }
    } else {
        $displayCompanyLogo = asset('images/logo.png');
    }
@endphp

<header
    class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">

    <!-- Kiri: Info Perusahaan / Tenant Aktif -->
    <div class="flex items-center gap-3">
        <div class="w-auto h-6 flex items-center justify-center overflow-hidden">
            <img src="{{ $displayCompanyLogo }}" alt="{{ $displayCompanyName }}"
                class="max-w-full max-h-full object-contain">
        </div>
        <div>
            <span
                class="text-[10px] font-black uppercase tracking-wider text-slate-400 block leading-none mb-0.5">Perusahaan</span>
            <h2 class="text-sm font-bold text-slate-800 leading-tight">{{ $displayCompanyName }}</h2>
        </div>
    </div>

    <!-- Kanan: User Profile Dropdown (Alpine.js) -->
    <div class="flex items-center gap-5">

        <!-- User Profile Dropdown -->
        <div x-data="{ open: false }" class="relative">

            <!-- Trigger Button -->
            <button @click="open = !open" @click.away="open = false"
                class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition focus:outline-none">

                <!-- Avatar User -->
                <img src="{{ $userAvatar }}" alt="{{ $userName }}"
                    class="w-9 h-9 rounded-full object-cover border-2 border-brand-gold transition">

                <!-- Nama & Role -->
                <div class="text-left hidden md:block leading-tight pr-1">
                    <span
                        class="text-xs font-bold text-slate-800 block truncate max-w-[120px]">{{ $userName }}</span>
                    <span class="text-[10px] font-medium text-[#C59B27] capitalize block">{{ $userRole }}</span>
                </div>

                <!-- Chevron Icon -->
                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Menu Dropdown -->
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-100 py-1.5 z-50 divide-y divide-slate-100"
                style="display: none;">

                <!-- Identitas Singkat di Dropdown -->
                <div class="px-4 py-2.5">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ $userName }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ $currentUser->email ?? 'admin@wayang.com' }}</p>
                </div>

                <!-- Menu Item -->
                <div class="py-1 text-xs">
                    <a href="#"
                        class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile Saya</span>
                    </a>

                    <a href="#"
                        class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>Pengaturan Akun</span>
                    </a>
                </div>

                <!-- Action Logout -->
                <div class="py-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition text-left">
                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</header>
