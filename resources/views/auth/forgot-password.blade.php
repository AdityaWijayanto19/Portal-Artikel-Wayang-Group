<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - {{ config('app.name') }}</title>
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

            <div class="pt-12 pb-6 px-10 text-center">
                <p class="text-gray-400 text-sm font-medium tracking-wide mb-1">Lupa Password?</p>
                <h1 class="text-gray-800 font-bold text-xl tracking-tight">Masukkan email untuk reset password</h1>
            </div>

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
                        <div class="flex-1">
                            <p class="text-sm text-red-700 font-medium">{{ session('block') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Global Error Alert --}}
            @if (session('error'))
                <div class="mx-10 mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-start gap-3">
                        <div class="p-1.5 bg-red-100 rounded-lg text-red-500 shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                            @if (str_contains(session('error'), 'tidak terdaftar'))
                                <a href="https://wa.me/6282213840105?text=Halo%20Superadmin%2C%20saya%20butuh%20bantuan%20terkait%20lupa%20password"
                                    target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 mt-2.5 text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                    </svg>
                                    Hubungi Superadmin via WhatsApp
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="px-10 pb-8">
                @csrf

                <div class="mb-8 relative">
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        placeholder="Email"
                        class="w-full py-3 bg-transparent border-b @error('email') border-red-500 @else border-gray-200 @enderror focus:border-[#E9C852] focus:outline-none transition-colors duration-300 placeholder-gray-400 text-gray-700 text-sm"
                        required autofocus>
                    @error('email')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full bg-[#E9C852] hover:bg-[#E3C045] text-gray-800 font-semibold py-3 rounded-lg text-sm shadow-sm hover:shadow transition-all duration-300">
                    Kirim Link Reset Password
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
