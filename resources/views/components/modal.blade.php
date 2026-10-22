@props([
    'name',
    'title' => null,
])

<div x-data="{ show: false }"
     x-show="show"
     x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     x-on:keydown.escape.window="show = false"
     x-cloak
     class="fixed inset-0 z-[9999] overflow-y-auto"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="show"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="show = false"
         class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs"></div>

    <!-- Modal Box -->
    <div class="min-h-screen px-4 text-center flex items-center justify-center">
        <div x-show="show"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="inline-block w-full max-w-lg p-6 my-8 text-left transition-all transform bg-white border border-slate-200 rounded-xl shadow-2xl relative z-10">

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
                @if($title)
                    <h3 class="text-sm font-bold text-slate-800">{{ $title }}</h3>
                @endif
                {{-- type="button" WAJIB: tanpa itu, di dalam <form> utama, tombol ini
                     ber-default type="submit" dan klik X ikut mengirim form. --}}
                <button type="button" @click="show = false" aria-label="Tutup"
                    class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg p-1 -mr-1 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
