@props([
    'name' => 'tags',
    'label' => 'Tag',
    'value' => [], // array nama tag terpilih (untuk mode edit / old input)
    'placeholder' => 'Ketik tag lalu tekan Enter atau koma...',
])

@php
    // Normalisasi nilai awal: bisa dari old() (array string), Collection model Tag, atau array.
    $initial = old($name, $value);
    if ($initial instanceof \Illuminate\Support\Collection) {
        $initial = $initial->pluck('name')->all();
    }
    $initial = collect($initial ?? [])
        ->map(fn ($t) => is_object($t) ? ($t->name ?? '') : (string) $t)
        ->filter()
        ->values()
        ->all();
@endphp

{{--
    Tag input freeform (badge/chip). Pengguna mengetik lalu Enter atau koma untuk membuat tag
    baru — TANPA perlu terdaftar di DB lebih dulu (service meng-upsert nama→id per company).
    Setiap tag dikirim sebagai <input type="hidden" name="tags[]"> sehingga terbaca sebagai
    array oleh StoreArticleRequest. Chip bisa dihapus (×).
--}}
<div class="space-y-1.5"
    x-data="tagInput(@js($initial))"
>
    @if ($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <div
        class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-2.5 py-2 flex flex-wrap gap-1.5 items-center focus-within:border-[#C59B27] focus-within:ring-1 focus-within:ring-[#C59B27] transition shadow-xs"
        @click="$refs.tagField.focus()"
    >
        {{-- Chip tag --}}
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 bg-[#C59B27]/10 text-[#8a6d15] text-[11px] font-semibold px-2 py-1 rounded-lg border border-[#C59B27]/30">
                <span x-text="tag"></span>
                <button type="button" @click="remove(index)" class="text-[#8a6d15]/70 hover:text-rose-500 leading-none text-sm">&times;</button>
                {{-- Hidden input agar terkirim sebagai tags[] --}}
                <input type="hidden" :name="'{{ $name }}[]'" :value="tag">
            </span>
        </template>

        {{-- Field ketik --}}
        <input
            type="text"
            x-ref="tagField"
            x-model="draft"
            @keydown.enter.prevent="commit()"
            @keydown.comma.prevent="commit()"
            @keydown.backspace="draft === '' && removeLast()"
            @blur="commit()"
            placeholder="{{ $placeholder }}"
            class="flex-1 min-w-[120px] bg-transparent text-xs text-slate-800 focus:outline-none placeholder:text-slate-400 py-1"
        >
    </div>

    <p class="text-[11px] text-slate-400">Pisahkan dengan Enter atau koma. Tag baru dibuat otomatis.</p>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            function tagInput(initial) {
                return {
                    tags: Array.isArray(initial) ? [...initial] : [],
                    draft: '',
                    commit() {
                        const value = this.draft.trim().replace(/,+$/, '').trim();
                        if (value === '') { this.draft = ''; return; }
                        // Hindari duplikat (case-insensitive).
                        const exists = this.tags.some(t => t.toLowerCase() === value.toLowerCase());
                        if (!exists) { this.tags.push(value); }
                        this.draft = '';
                    },
                    remove(index) {
                        this.tags.splice(index, 1);
                    },
                    removeLast() {
                        if (this.tags.length) { this.tags.pop(); }
                    },
                };
            }
        </script>
    @endpush
@endonce
