<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-[#F4F7F9] min-h-screen flex flex-col justify-between overflow-x-hidden relative">

    <div
        class="absolute inset-0 w-full h-full z-0 pointer-events-none select-none overflow-hidden flex items-center justify-center">
        <img src="{{ asset('images/login-bg.png') }}" alt="Background Landscape"
            class="w-full h-full object-contain object-center">
    </div>

    <header class="w-full px-6 md:px-12 py-6 flex justify-between items-center z-10 relative">
        <div class="flex items-center">
            <img src="{{ asset('images/logo.png') }}" alt="Wayang Group Logo" class="w-10 h-10 mr-3">
            <span class="text-amber-900 font-bold text-lg tracking-tight">Wayang Group</span>
        </div>
        <span class="sr-only">Automation Artikel Wayang Group Company</span>
    </header>

    <main class="flex-1 flex items-center justify-center relative px-4 py-8 z-10">

        <div
            class="w-full max-w-[450px] bg-white rounded-2xl shadow-[0_15px_50px_-15px_rgba(0,0,0,0.08)] overflow-hidden border border-gray-100/50">

            @if (!empty($invalidToken) || !empty($expiredToken))
                {{-- Token Invalid / Expired --}}
                <div class="pt-12 pb-6 px-10 text-center">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <p class="text-gray-400 text-sm font-medium tracking-wide mb-1">
                        @if (!empty($expiredToken))
                            Link Kadaluarsa
                        @else
                            Link Tidak Valid
                        @endif
                    </p>
                    <h1 class="text-gray-800 font-bold text-xl tracking-tight">
                        @if (!empty($expiredToken))
                            Link reset password sudah kadaluarsa
                        @else
                            Link reset password tidak valid
                        @endif
                    </h1>
                </div>

                <div class="px-10 pb-4">
                    <p class="text-sm text-gray-500 text-center leading-relaxed">
                        @if (!empty($expiredToken))
                            Link reset password yang Anda gunakan sudah kedaluwarsa. Silakan request link baru.
                        @else
                            Link reset password yang Anda gunakan tidak valid atau sudah digunakan.
                        @endif
                    </p>
                </div>

                <div class="px-10 pb-8">
                    <a href="{{ route('password.request') }}"
                        class="block w-full bg-[#E9C852] hover:bg-[#E3C045] text-gray-800 font-semibold py-3 rounded-lg text-sm shadow-sm hover:shadow transition-all duration-300 text-center">
                        Request Link Baru
                    </a>
                </div>

            @else
                {{-- Form Reset Password --}}
                <div class="pt-12 pb-6 px-10 text-center">
                    <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-[#c59b27]" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <p class="text-gray-400 text-sm font-medium tracking-wide mb-1">Atur Password Baru</p>
                    <h1 class="text-gray-800 font-bold text-xl tracking-tight">Masukkan password baru Anda</h1>
                </div>

                {{-- Success Alert --}}
                @if (session('success'))
                    <div class="mx-10 mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-1.5 bg-emerald-100 rounded-lg text-emerald-500 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-emerald-700 font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Throttle Block --}}
                @if (session('block'))
                    <div class="mx-10 mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-start gap-3">
                            <div class="p-1.5 bg-red-100 rounded-lg text-red-500 shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                            <p class="text-sm text-red-700 font-medium">{{ session('block') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Error Alert --}}
                @if (session('error'))
                    <div class="mx-10 mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="p-1.5 bg-red-100 rounded-lg text-red-500 shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                            </div>
                            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <form action="{{ route('password.update') }}" method="POST" class="px-10 pb-8">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email }}">

                    <div class="mb-6 relative" x-data="{ showPassword: false }">
                        <input type="password" name="password" id="password" placeholder="Password baru"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full py-3 pr-10 bg-transparent border-b @error('password') border-red-500 @else border-gray-200 @enderror focus:border-[#E9C852] focus:outline-none transition-colors duration-300 placeholder-gray-400 text-gray-700 text-sm"
                            required autofocus>
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-0 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" x2="22" y1="2" y2="22" />
                            </svg>
                        </button>
                        @error('password')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-8 relative" x-data="{ showPassword: false }">
                        <input type="password" name="password_confirmation" id="password_confirmation"
                            placeholder="Konfirmasi password baru"
                            :type="showPassword ? 'text' : 'password'"
                            class="w-full py-3 pr-10 bg-transparent border-b border-gray-200 focus:border-[#E9C852] focus:outline-none transition-colors duration-300 placeholder-gray-400 text-gray-700 text-sm"
                            required>
                        <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-0 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none"
                            :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                                <line x1="2" x2="22" y1="2" y2="22" />
                            </svg>
                        </button>
                    </div>

                    <button type="submit"
                        class="w-full bg-[#E9C852] hover:bg-[#E3C045] text-gray-800 font-semibold py-3 rounded-lg text-sm shadow-sm hover:shadow transition-all duration-300">
                        Simpan Password Baru
                    </button>
                </form>

                <div class="px-10 pb-8 text-center">
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 font-medium transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        Kembali ke login
                    </a>
                </div>
            @endif
        </div>
    </main>

    <footer
        class="w-full px-6 md:px-12 py-6 flex flex-col sm:flex-row justify-between items-center text-xs text-gray-700 gap-4 z-10 relative">
        <div class="flex gap-6">
            <a href="#" class="hover:text-gray-900 hover:underline transition-colors">Privacy</a>
            <a href="#" class="hover:text-gray-900 hover:underline transition-colors">Terms</a>
            <a href="#" class="hover:text-gray-900 hover:underline transition-colors">Faq</a>
        </div>

        <p class="text-center text-xs">
            &copy; {{ date('Y') }} Wayang Group. semua hak dilindungi undang-undang.
        </p>
    </footer>

</body>

</html>
