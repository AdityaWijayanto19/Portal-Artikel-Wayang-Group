<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Wayang Group Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')

    {{-- Dynamic Multi-Tenant Branding: CSS Variables dari Perusahaan Aktif --}}
    @php
        use App\Helpers\ColorHelper;

        $brandPrimary     = ColorHelper::normalizeHex($activeCompany?->primary_color, ColorHelper::DEFAULT_PRIMARY);
        $brandSidebar     = ColorHelper::normalizeHex($activeCompany?->sidebar_color, ColorHelper::DEFAULT_SIDEBAR);
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
    </style>
</head>

<body class="h-full bg-slate-50 text-slate-800 font-sans antialiased overflow-hidden">

    <div class="h-screen flex overflow-hidden">

        <aside class="w-64 bg-brand-sidebar shrink-0 h-full border-r border-brand-border hidden lg:block overflow-y-auto">
            @include('partials.navigation')
        </aside>

        <div class="flex-1 flex flex-col min-w-0 h-full overflow-y-auto">

            @include('partials.header')

            <main class="flex-1 p-6 max-w-6xl">
                @if (View::hasSection('title') || View::hasSection('header'))
                    <div class="mb-6">
                        @hasSection('header')
                            {{--  --}}
                            @yield('header')
                        @else
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    @hasSection('back_url')
                                        <a href="@yield('back_url')"
                                            class="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                            </svg>
                                        </a>
                                    @endif

                                    <div>
                                        <h1 class="text-xl font-bold font-sans text-slate-900 tracking-wide">
                                            @yield('title')</h1>
                                        @hasSection('subtitle')
                                            <p class="text-xs text-slate-500 mt-0.5">@yield('subtitle')</p>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    @yield('header_actions')
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
    <x-toast />
</body>

</html>
