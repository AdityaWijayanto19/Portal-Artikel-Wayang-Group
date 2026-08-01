<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Artikel Wayang Group Company</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
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
                <p class="text-gray-400 text-sm font-medium tracking-wide mb-1">Selamat Datang!</p>
                <h1 class="text-gray-800 font-bold text-xl tracking-tight">Silakan masuk ke akun Anda</h1>
            </div>

            <form action="{{ route('login.store') }}" method="POST" class="px-10 pb-8">
                @csrf

                {{-- Alert Global: Jika Akun Tidak Ditemukan --}}
                @error('login_global')
                    <div
                        class="mb-6 p-3.5 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl flex items-center gap-2 animate-shake">
                        <svg class="w-4 h-4 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                {{-- Field Email / Username --}}
                <div class="mb-6 relative">
                    <input type="text" name="login" id="login" value="{{ old('login') }}"
                        placeholder="Email/Username"
                        class="w-full py-3 bg-transparent border-b @error('login') border-red-500 @else border-gray-200 @enderror focus:border-[#E9C852] focus:outline-none transition-colors duration-300 placeholder-gray-400 text-gray-700 text-sm"
                        required autofocus>
                    @error('login')
                        <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Field Password --}}
                <div class="mb-8 relative">
                    <input type="password" name="password" id="password" placeholder="Password"
                        class="w-full py-3 bg-transparent border-b @error('password') border-red-500 @else border-gray-200 @enderror focus:border-[#E9C852] focus:outline-none transition-colors duration-300 placeholder-gray-400 text-gray-700 text-sm"
                        required>
                    {{-- Alert Khusus: Jika Password Salah --}}
                    @error('password')
                        <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <a href="#"
                        class="text-xs text-gray-500 hover:text-gray-700 font-medium hover:underline transition-colors">
                        Lupa Password?
                    </a>

                    <button type="submit"
                        class="bg-[#E9C852] hover:bg-[#E3C045] text-gray-800 font-semibold px-8 py-2.5 rounded-lg text-xs shadow-sm hover:shadow transition-all duration-300">
                        Masuk
                    </button>
                </div>
            </form>
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
