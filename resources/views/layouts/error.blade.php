<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Terjadi Kesalahan' }} - Portal Artikel</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700;800&family=Inter:wght@400;600&display=swap"
        rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    @php
        use App\Helpers\ColorHelper;

        $company = $activeCompany ?? null;
        $brandPrimary = ColorHelper::normalizeHex($company?->primary_color, ColorHelper::DEFAULT_PRIMARY);
        $brandSidebar = ColorHelper::normalizeHex($company?->sidebar_color, ColorHelper::DEFAULT_SIDEBAR);
        $brandPrimaryText = ColorHelper::getContrastTextColor($brandPrimary);
        $brandSidebarText = ColorHelper::getContrastTextColor($brandSidebar);
    @endphp

    <style>
        :root {
            --color-primary: {{ $brandPrimary }};
            --color-primary-text: {{ $brandPrimaryText }};
            --color-sidebar: {{ $brandSidebar }};
            --color-sidebar-text: {{ $brandSidebarText }};
        }

        .stars-bg {
            background-image:
                radial-gradient(1.5px 1.5px at 10% 15%, #000000 100%, transparent),
                radial-gradient(1px 1px at 20% 35%, #000000 100%, transparent),
                radial-gradient(2px 2px at 30% 70%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 40% 25%, #000000 100%, transparent),
                radial-gradient(1px 1px at 50% 85%, #000000 100%, transparent),
                radial-gradient(2px 2px at 60% 10%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 70% 50%, #000000 100%, transparent),
                radial-gradient(1px 1px at 80% 30%, #000000 100%, transparent),
                radial-gradient(2px 2px at 88% 80%, #000000 100%, transparent),
                radial-gradient(1.5px 1.5px at 95% 15%, #000000 100%, transparent);
        }

        .planet {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath stroke='none' d='M0 0h24v24H0z' fill='none'/%3E%3Cpath d='M18.816 13.58c2.292 2.138 3.546 4 3.092 4.9c-.745 1.46 -5.783 -.259 -11.255 -3.838c-5.47 -3.579 -9.304 -7.664 -8.56 -9.123c.464 -.91 2.926 -.444 5.803 .805'/%3E%3Cpath d='M5 12a7 7 0 1 0 14 0a7 7 0 1 0 -14 0'/%3E%3C/svg%3E");
            background-size: contain;
            background-repeat: no-repeat;
        }

        .digit {
            position: relative;
            display: inline-block;
            -webkit-text-stroke: 3px #5a0091;
            paint-order: stroke fill;
            filter: drop-shadow(0px 12px 18px rgba(157, 0, 255, 0.25));
            transition: transform 0.3s ease;
        }

        .digit:hover {
            transform: scale(1.04);
        }

        .digit-1 { z-index: 1; margin-right: -0.12em; }
        .digit-2 { z-index: 2; margin-right: -0.12em; }
        .digit-3 { z-index: 3; }

        .btn-home {
            display: inline-block;
            background-color: #9d00ff;
            color: #ffffff;
            padding: 12px 36px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(157, 0, 255, 0.4);
            transition: all 0.2s ease;
        }

        .btn-home:hover {
            background-color: #b128ff;
            box-shadow: 0 6px 28px rgba(157, 0, 255, 0.7);
            transform: translateY(-2px);
        }

        .btn-home:active {
            transform: translateY(0);
        }

        @media (max-width: 640px) {
            .planet-1 { left: 5%; }
            .planet-3 { right: 7%; }
            .planet-4 { right: 10%; }
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen bg-white text-[#050505] relative overflow-hidden font-['Inter',sans-serif]">

    <div class="stars-bg absolute inset-0 w-full h-full z-[1] pointer-events-none"></div>

    <div class="planet absolute w-9 h-9 opacity-80 z-[1] pointer-events-none planet-1 top-[32%] left-[12%] rotate-[-15deg]"></div>
    <div class="planet absolute w-9 h-9 opacity-80 z-[1] pointer-events-none planet-2 top-[12%] left-[45%] rotate-[10deg] scale-[0.7]"></div>
    <div class="planet absolute w-9 h-9 opacity-80 z-[1] pointer-events-none planet-3 top-[36%] right-[19%] rotate-[-25deg] scale-[0.8]"></div>
    <div class="planet absolute w-9 h-9 opacity-80 z-[1] pointer-events-none planet-4 bottom-[20%] right-[27%] rotate-[15deg]"></div>

    <main class="relative z-10 w-full max-w-[600px] p-5 text-center sm:p-4">
        <div class="flex justify-center items-center font-['Fredoka',sans-serif] text-[clamp(140px,22vw,240px)] font-bold leading-[0.85] text-[#9d00ff] select-none mb-6 sm:mb-5">
            @yield('error-code')
        </div>

        <h1 class="text-[22px] font-semibold mb-2.5 text-[#050505] tracking-[0.3px] sm:text-xl">
            @yield('title')
        </h1>

        <p class="text-sm text-[#52525b] leading-relaxed mb-8 font-normal sm:text-[13px] sm:mb-7">
            @yield('description')
        </p>

        @yield('action')
    </main>

</body>

</html>
