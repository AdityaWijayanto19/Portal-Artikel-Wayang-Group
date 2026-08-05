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
     class="fixed inset-0 z-50 overflow-y-auto"
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
         class="fixed inset-0 bg-black/70 backdrop-blur-xs"></div>

    <!-- Modal Box -->
    <div class="min-h-screen px-4 text-center flex items-center justify-center">
        <div x-show="show"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="inline-block w-full max-w-lg p-6 my-8 text-left transition-all transform bg-brand-card border border-brand-border rounded-xl shadow-2xl relative z-10">

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-brand-border">
                @if($title)
                    <h3 class="text-sm font-bold text-brand-text uppercase tracking-wider">{{ $title }}</h3>
                @endif
                <button @click="show = false" class="text-brand-muted hover:text-brand-text transition">
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
