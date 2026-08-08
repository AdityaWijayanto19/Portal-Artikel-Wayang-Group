{{-- resources/views/components/toast.blade.php --}}
<div x-data="{
        show: true,
        init() {
            // Otomatis hilang dalam 4 detik
            setTimeout(() => { this.show = false }, 5000);
        }
     }"
     class="fixed top-[12%] right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">

    {{-- ALERT SUCCESS --}}
    @if (session('success'))
        <div x-show="show"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-center justify-between gap-3 p-4 bg-white border-l-4 border-emerald-500 rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100">

            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Berhasil</h4>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">{{ session('success') }}</p>
                </div>
            </div>

            <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- ALERT ERROR SYSTEM --}}
    @if (session('error'))
        <div x-show="show"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="pointer-events-auto flex items-center justify-between gap-3 p-4 bg-white border-l-4 border-rose-500 rounded-xl shadow-xl shadow-slate-200/50 border border-slate-100">

            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-50 rounded-lg text-rose-600 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-rose-600">Gagal</h4>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">{{ session('error') }}</p>
                </div>
            </div>

            <button @click="show = false" class="text-slate-400 hover:text-slate-600 transition p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

</div>
