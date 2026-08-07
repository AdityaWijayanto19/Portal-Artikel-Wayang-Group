@props([
    'action',
    'title' => 'Hapus Data?',
])

<div x-data="{ open: false }" class="inline-block">
    {{-- Tombol Trigger --}}
    <button type="button" @click="open = true"
        class="p-1.5 inline-flex items-center justify-center bg-rose-50 hover:bg-rose-100 text-rose-600 rounded transition"
        title="Hapus">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
    </button>

    {{-- Modal Teleport ke Body agar tidak terjebak styling parent --}}
    <template x-teleport="body">
        <div x-show="open"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             aria-labelledby="modal-title"
             role="dialog"
             aria-modal="true">

            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">

                {{-- Overlay Dark Backdrop --}}
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="open = false"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

                {{-- Card Modal Box --}}
                <div x-show="open"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-6">

                    <div class="sm:flex sm:items-start gap-4">
                        {{-- Icon Warning --}}
                        <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 sm:mx-0">
                            <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>

                        {{-- Text Content --}}
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1 min-w-0">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">
                                {{ $title }}
                            </h3>
                            <div class="mt-2 text-sm text-slate-500 break-words">
                                {{ $message }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions / Buttons --}}
                    <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
                        <form action="{{ $action }}" method="POST" class="inline-flex w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 transition">
                                Ya, Hapus
                            </button>
                        </form>
                        <button type="button" @click="open = false"
                            class="mt-3 inline-flex w-full justify-center rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-200 transition sm:mt-0 sm:w-auto">
                            Batal
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </template>
</div>
