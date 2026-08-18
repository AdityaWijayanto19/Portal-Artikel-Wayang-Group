@php
    use Illuminate\Support\Facades\Storage;
    use App\Helpers\ColorHelper;

    $currentUser = $currentUser ?? auth()->user();
    $userRole = str_replace('_', ' ', $currentUser->roles->first()?->name ?? 'admin pic');
    $userName = $currentUser->name ?? 'Yunnappie';

    // Perbaikan: Pastikan $globalCompanies selalu ada
    $companiesList = $globalCompanies ?? collect();
    $activeCompanyId = session('active_company_id');
    $activeCompany = $activeCompany ?? null;

    if ($currentUser->isSuperAdmin()) {
        if ($activeCompanyId && $activeCompanyId !== 'all') {
            $activeCompany = $activeCompany ?: $companiesList->firstWhere('id', $activeCompanyId);
        }
    } else {
        // Perbaikan: Menggunakan $currentUser->company (BelongsTo) bukan $currentUser->companies (null)
        $activeCompany = $currentUser->company;
    }

    // Assign Nama Perusahaan
    $displayCompanyName = $activeCompany->name ?? 'Wayang Group';

    // Ambil kolom logo
    $rawLogo = $activeCompany?->logo_path;

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

    // Warna avatar fallback mengikuti branding perusahaan aktif (ui-avatars butuh hex tanpa '#')
    $avatarTextColor = ltrim(ColorHelper::normalizeHex($activeCompany?->primary_color, ColorHelper::DEFAULT_PRIMARY), '#');
    $avatarBgColor = ltrim(ColorHelper::normalizeHex($activeCompany?->sidebar_color, ColorHelper::DEFAULT_SIDEBAR), '#');

    $userAvatar =
        $currentUser->profile_photo_url ??
        'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&color=' . $avatarTextColor . '&background=' . $avatarBgColor;
@endphp

<header
    class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm">

    <!-- Kiri: Info Perusahaan / Tenant Aktif -->
    <div class="flex items-center gap-3">
        @hasrole('super_admin')
            {{-- Tampilan khusus Super Admin (Active Tenant Context) --}}
            <div class="w-auto h-6 flex items-center justify-center overflow-hidden">
                <img src="{{ $displayCompanyLogo }}" alt="{{ $displayCompanyName }}"
                    class="max-w-full max-h-full object-contain">
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block leading-none mb-0.5">
                    Perusahaan
                </span>
                <h2 class="text-sm font-bold text-slate-800 leading-tight">{{ $displayCompanyName }}</h2>
            </div>
        @endhasrole

        @hasanyrole('company_admin|admin|author')
            @php
                $hour = now()->hour;
                if ($hour >= 5 && $hour < 11) {
                    $greeting = 'Selamat Pagi';
                } elseif ($hour >= 11 && $hour < 15) {
                    $greeting = 'Selamat Siang';
                } elseif ($hour >= 15 && $hour < 18) {
                    $greeting = 'Selamat Sore';
                } else {
                    $greeting = 'Selamat Malam';
                }
            @endphp

            <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block leading-none mb-0.5">
                    {{ $greeting }}
                </span>
                <h2 class="text-sm font-bold text-slate-800 leading-tight">
                    {{ auth()->user()->name }}
                </h2>
            </div>
        @endhasanyrole
    </div>

    <!-- Kanan: Panduan & User Profile Dropdown (Alpine.js) -->
    <div class="flex items-center gap-5">

        <!-- Link Panduan Penulis -->
        <a href="{{ Route::has('guidelines.index') ? route('guidelines.index') : 'javascript:void(0)' }}"
            class="hidden sm:flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition {{ request()->routeIs('guidelines.index') ? 'bg-brand/10 text-brand' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <span class="hidden md:inline">Panduan Penulis</span>
        </a>

        <!-- User Profile Dropdown -->
        <div x-data="{ open: false }" class="relative">

            <!-- Trigger Button -->
            <button @click="open = !open" @click.away="open = false"
                class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-slate-100 transition focus:outline-none">

                <!-- Avatar User -->
                <img src="{{ $userAvatar }}" alt="{{ $userName }}"
                    class="w-9 h-9 rounded-full object-cover border-2 border-brand transition">

                <!-- Nama & Role -->
                <div class="text-left hidden md:block leading-tight pr-1">
                    <span
                        class="text-xs font-bold text-slate-800 block truncate max-w-[120px]">{{ $userName }}</span>
                    <span class="text-[10px] font-medium text-brand capitalize block">{{ $userRole }}</span>
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
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center gap-2.5 px-4 py-2 text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile Saya</span>
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
