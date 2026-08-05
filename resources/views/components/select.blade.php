@props([
    'name',
    'label' => null,
    'options' => [], // Format: ['value' => 'Label'] atau [['value' => '1', 'label' => 'Option 1']]
    'placeholder' => 'Pilih salah satu...',
    'required' => false,
    'value' => null,
    'searchable' => false,
])

@php
    // Normalisasi format options agar konsisten (support array asosiatif maupun array of arrays/objects)
    $formattedOptions = collect($options)
        ->map(function ($label, $key) {
            if (is_array($label)) {
                return [
                    'value' => (string) ($label['id'] ?? ($label['value'] ?? $key)),
                    'label' => (string) ($label['name'] ?? ($label['label'] ?? reset($label))),
                ];
            }
            if (is_object($label)) {
                return [
                    'value' => (string) ($label->id ?? ($label->value ?? $key)),
                    'label' => (string) ($label->name ?? ($label->label ?? '')),
                ];
            }
            return [
                'value' => (string) $key,
                'label' => (string) $label,
            ];
        })
        ->values()
        ->toArray();

    $selectedValue = old($name, $value);
@endphp

<div class="space-y-1.5 relative w-full"
    x-data="{
        open: false,
        search: '',
        value: '{{ $selectedValue }}',
        options: {{ json_encode($formattedOptions) }},
        highlightedIndex: 0,

        get selectedLabel() {
            let found = this.options.find(opt => String(opt.value) === String(this.value));
            return found ? found.label : '';
        },

        get filteredOptions() {
            if (!this.search) return this.options;
            return this.options.filter(opt =>
                opt.label.toLowerCase().includes(this.search.toLowerCase())
            );
        },

        selectOption(val) {
            this.value = val;
            this.open = false;
            this.search = '';
            $dispatch('change', val);
        },

        highlightNext() {
            if (this.highlightedIndex < this.filteredOptions.length - 1) {
                this.highlightedIndex++;
            }
        },

        highlightPrev() {
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
            }
        },

        selectHighlighted() {
            if (this.filteredOptions.length > 0 && this.filteredOptions[this.highlightedIndex]) {
                this.selectOption(this.filteredOptions[this.highlightedIndex].value);
            }
        }
    }"
    @click.outside="open = false"
    @keydown.escape.window="open = false">

    <!-- Label -->
    @if ($label)
        <label for="{{ $name }}_button"
            class="block text-xs font-bold text-slate-700 uppercase tracking-wider select-none">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <!-- Hidden Input untuk Form Submit -->
    <input type="hidden" name="{{ $name }}" :value="value" {{ $required ? 'required' : '' }}>

    <!-- Trigger Button -->
    <button type="button" id="{{ $name }}_button"
        @click="open = !open; if(open) $nextTick(() => $refs.searchInput?.focus())"
        @keydown.arrow-down.prevent="if(!open) { open = true; } else { highlightNext(); }"
        @keydown.arrow-up.prevent="if(open) highlightPrev();"
        @keydown.enter.prevent="if(open) selectHighlighted();"
        {{ $attributes->merge([
            'class' =>
                'w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 text-left flex items-center justify-between focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition shadow-xs cursor-pointer select-none ' .
                ($errors->has($name) ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500' : ''),
        ]) }}>

        <span x-text="selectedLabel || '{{ $placeholder }}'"
            :class="{ 'text-slate-400': !selectedLabel, 'text-slate-800 font-medium': selectedLabel }"
            class="truncate">
        </span>

        <!-- Chevron Icon dengan Rotasi Halus -->
        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2"
            :class="{ 'rotate-180 text-[#C59B27]': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Panel (Floating Card) -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
        style="display: none;"
        class="absolute left-0 right-0 z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden flex flex-col py-1 max-h-60">

        <!-- Input Pencarian (Muncul jika searchable="true") -->
        @if ($searchable)
            <div class="p-2 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10">
                <div class="relative">
                    <input type="text"
                        x-ref="searchInput"
                        x-model="search"
                        @keydown.arrow-down.prevent="highlightNext()"
                        @keydown.arrow-up.prevent="highlightPrev()"
                        @keydown.enter.prevent="selectHighlighted()"
                        placeholder="Cari..."
                        class="w-full bg-white text-slate-800 text-xs border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#C59B27]">

                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        @endif

        <!-- Options List -->
        <ul class="overflow-y-auto divide-y divide-slate-50 flex-1">
            <template x-for="(opt, index) in filteredOptions" :key="opt.value">
                <li @click="selectOption(opt.value)"
                    @mouseenter="highlightedIndex = index"
                    :class="{
                        'bg-amber-50/80 text-[#C59B27] font-semibold': String(opt.value) === String(value),
                        'bg-slate-100 text-slate-900': index === highlightedIndex && String(opt.value) !== String(value),
                        'text-slate-700 hover:bg-slate-50': String(opt.value) !== String(value) && index !== highlightedIndex
                    }"
                    class="px-3.5 py-2 text-xs cursor-pointer flex items-center justify-between transition select-none">

                    <span x-text="opt.label" class="truncate"></span>

                    <!-- Checkmark Icon untuk Item yang Terpilih -->
                    <template x-if="String(opt.value) === String(value)">
                        <svg class="w-3.5 h-3.5 text-[#C59B27] shrink-0 ml-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </li>
            </template>

            <!-- State Ketika Pencarian Kosong -->
            <template x-if="filteredOptions.length === 0">
                <li class="px-3.5 py-4 text-xs text-center text-slate-400 select-none">
                    Tidak ada data ditemukan.
                </li>
            </template>
        </ul>
    </div>

    <!-- Error Message -->
    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>
