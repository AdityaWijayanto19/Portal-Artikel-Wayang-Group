@props([
    'headers' => [],
    'searchPlaceholder' => 'Cari data...',
    'searchAction' => null,
    'searchName' => 'search',
    'searchValue' => null,
    'pagination' => null,
    'actions' => null,
    'containerId' => 'table-container',
])

@php
    $currentSearchValue = $searchValue ?? request($searchName, '');
@endphp

<div id="{{ $containerId }}" class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">

    {{-- Search & Filter Bar --}}
    @if($searchAction || isset($filters) || $actions)
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">

            @if($searchAction)
                <form action="{{ $searchAction }}" method="GET" x-ref="filterForm" class="flex flex-wrap sm:flex-nowrap items-center gap-3 flex-1 max-w-md">

                    {{-- Live Search Alpine Container --}}
                    <div class="relative w-full" x-data="{
                        search: '{{ $currentSearchValue }}',
                        loading: false,
                        performSearch() {
                            this.loading = true;

                            // Cari form via $refs atau via elemen terdekat (fallback)
                            const form = this.$refs.filterForm || this.$el.closest('form');
                            const actionUrl = form ? form.action : window.location.href;

                            const formData = form ? new FormData(form) : new FormData();
                            const url = new URL(actionUrl, window.location.origin);

                            // Masukkan input/filter ke query string
                            for (const [key, val] of formData.entries()) {
                                if (val && val !== 'all') {
                                    url.searchParams.set(key, val);
                                } else {
                                    url.searchParams.delete(key);
                                }
                            }

                            // Reset pagination ke halaman 1 saat pencarian baru
                            url.searchParams.delete('page');

                            fetch(url.toString(), {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            })
                            .then(res => res.text())
                            .then(html => {
                                const parser = new DOMParser();
                                const doc = parser.parseFromString(html, 'text/html');

                                const targetId = '{{ $containerId }}';
                                const newContainer = doc.getElementById(targetId);
                                const currentContainer = document.getElementById(targetId);

                                if (newContainer && currentContainer) {
                                    currentContainer.innerHTML = newContainer.innerHTML;
                                }

                                window.history.pushState({}, '', url.toString());
                            })
                            .catch(err => console.error('Search error:', err))
                            .finally(() => {
                                this.loading = false;
                            });
                        }
                    }">
                        <!-- Icon Search / Loading Spinner -->
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg x-show="!loading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <svg x-show="loading" class="animate-spin w-4 h-4 text-[#C59B27]" fill="none" viewBox="0 0 24 24" x-cloak>
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>

                        <!-- Input Search -->
                        <input
                            type="text"
                            name="{{ $searchName }}"
                            x-model="search"
                            @input.debounce.400ms="performSearch()"
                            placeholder="{{ $searchPlaceholder }}"
                            class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition placeholder:text-slate-400 text-slate-700"
                        />
                    </div>

                    {{-- Slot Filter Tambahan --}}
                    @if(isset($filters))
                        <div class="flex items-center gap-2">
                            {{ $filters }}
                        </div>
                    @endif
                </form>
            @endif

            @if($actions)
                <div class="flex items-center gap-2 justify-end">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    {{-- Main Table Container --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50/70 border-b border-slate-100">
                <tr>
                    @foreach($headers as $header)
                        @php
                            $label = is_array($header) ? ($header['label'] ?? '') : $header;
                            $align = is_array($header) ? ($header['align'] ?? 'left') : 'left';
                            $width = is_array($header) ? ($header['width'] ?? '') : '';

                            $alignClass = [
                                'center' => 'text-center',
                                'right' => 'text-right',
                            ][$align] ?? 'text-left';
                        @endphp

                        <th class="px-6 py-4 text-xs text-slate-500 font-bold uppercase tracking-wider select-none {{ $alignClass }} {{ $width }}">
                            {{ $label }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 text-slate-700 text-xs">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($pagination)
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/30">
            {{ $pagination }}
        </div>
    @endif
</div>
