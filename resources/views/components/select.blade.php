@props([
    'name',
    'label' => null,
    'options' => [],
    'placeholder' => 'Pilih salah satu...',
    'required' => false,
    'value' => null,
    'searchable' => false,
    'theme' => 'light',
    'showError' => true,
])

@php
    $dark = $theme === 'dark';

    $t = [
        'label' => $dark ? 'text-[#FDFBF7]/60' : 'text-slate-700',
        'button' => $dark ? 'bg-slate-800 text-slate-200 border-slate-700' : 'bg-white text-slate-800 border-slate-300',
        'placeholder' => $dark ? 'text-slate-500' : 'text-slate-400',
        'selectedText' => $dark ? 'text-slate-100 font-medium' : 'text-slate-800 font-medium',
        'panel' => $dark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-200',
        'searchWrap' => $dark ? 'bg-slate-900/40 border-slate-700' : 'bg-slate-50/50 border-slate-100',
        'searchInput' => $dark
            ? 'bg-slate-900 text-slate-200 border-slate-700 placeholder-slate-500'
            : 'bg-white text-slate-800 border-slate-200',
        'divide' => $dark ? 'divide-slate-700/60' : 'divide-slate-50',
        'optSelected' => 'bg-[#C59B27]/15 text-[#C59B27] font-semibold',
        'optHighlight' => $dark ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-900',
        'optDefault' => $dark ? 'text-slate-300 hover:bg-slate-700/50' : 'text-slate-700 hover:bg-slate-50',
    ];

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

<div class="space-y-1.5 relative w-full" x-data="{
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

        this.$nextTick(() => {
            const input = this.$refs.hiddenInput;
            if (input) {
                input.value = val;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
            $dispatch('change', val);
        });
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
}" @click.outside="open = false"
    @keydown.escape.window="open = false">

    <!-- Label -->
    @if ($label)
        <label for="{{ $name }}_button"
            class="block text-xs font-bold {{ $t['label'] }} uppercase tracking-wider select-none">
            {{ $label }}
            @if ($required)
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <input type="hidden" x-ref="hiddenInput" name="{{ $name }}" :value="value"
        {{ $required ? 'required' : '' }}>

    <button type="button" id="{{ $name }}_button"
        @click="open = !open; if(open) $nextTick(() => $refs.searchInput?.focus())"
        @keydown.arrow-down.prevent="if(!open) { open = true; } else { highlightNext(); }"
        @keydown.arrow-up.prevent="if(open) highlightPrev();" @keydown.enter.prevent="if(open) selectHighlighted();"
        {{ $attributes->merge([
            'class' =>
                'w-full ' .
                $t['button'] .
                ' text-xs border rounded-xl px-3.5 py-2.5 text-left flex items-center justify-between focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition shadow-xs cursor-pointer select-none ' .
                ($errors->has($name) ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500' : ''),
        ]) }}>

        <span x-text="selectedLabel || '{{ $placeholder }}'"
            :class="{ '{{ $t['placeholder'] }}': !selectedLabel, '{{ $t['selectedText'] }}': selectedLabel }"
            class="truncate">
        </span>

        <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0 ml-2"
            :class="{ 'rotate-180 text-[#C59B27]': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 -translate-y-1" style="display: none;"
        class="absolute left-0 right-0 z-50 mt-1.5 w-full {{ $t['panel'] }} border rounded-xl shadow-lg overflow-hidden flex flex-col py-1 max-h-60">

        @if ($searchable)
            <div class="p-2 border-b {{ $t['searchWrap'] }} sticky top-0 z-10">
                <div class="relative">
                    <input type="text" x-ref="searchInput" x-model="search"
                        @keydown.arrow-down.prevent="highlightNext()" @keydown.arrow-up.prevent="highlightPrev()"
                        @keydown.enter.prevent="selectHighlighted()" placeholder="Cari..."
                        class="w-full {{ $t['searchInput'] }} text-xs border rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#C59B27]">

                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        @endif

        <ul class="overflow-y-auto divide-y {{ $t['divide'] }} flex-1">
            <template x-for="(opt, index) in filteredOptions" :key="opt.value">
                <li @click="selectOption(opt.value)" @mouseenter="highlightedIndex = index"
                    :class="{
                        '{{ $t['optSelected'] }}': String(opt.value) === String(value),
                        '{{ $t['optHighlight'] }}': index === highlightedIndex && String(opt.value) !== String(value),
                        '{{ $t['optDefault'] }}': String(opt.value) !== String(value) && index !== highlightedIndex
                    }"
                    class="px-3.5 py-2 text-xs cursor-pointer flex items-center justify-between transition select-none">

                    <span x-text="opt.label" class="truncate"></span>

                    <template x-if="String(opt.value) === String(value)">
                        <svg class="w-3.5 h-3.5 text-[#C59B27] shrink-0 ml-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                </li>
            </template>

            <template x-if="filteredOptions.length === 0">
                <li class="px-3.5 py-4 text-xs text-center text-slate-400 select-none">
                    Tidak ada data ditemukan.
                </li>
            </template>
        </ul>
    </div>

    @if ($showError)
        @error($name)
            <p class="text-[11px] text-rose-500 font-medium mt-1">
                {{ $message }}
            </p>
        @enderror
    @endif
</div>
