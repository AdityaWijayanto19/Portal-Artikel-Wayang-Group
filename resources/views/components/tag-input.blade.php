@props([
    'name' => 'tags',
    'label' => 'Tag',
    'value' => [], // array nama tag terpilih (untuk mode edit / old input)
    'placeholder' => 'Ketik tag lalu tekan Enter atau koma...',
])

@php
    // Normalisasi nilai awal: bisa dari old() (string "a,b,c" ATAU array string),
    // Collection model Tag, atau array. Dipaksa menjadi array nama tag yang bersih.
    $initial = old($name, $value);
    if ($initial instanceof \Illuminate\Support\Collection) {
        $initial = $initial->pluck('name')->all();
    }
    if (is_string($initial)) {
        $initial = explode(',', $initial);
    }
    $initial = collect($initial ?? [])
        ->map(fn ($t) => is_object($t) ? ($t->name ?? '') : (string) $t)
        ->map(fn ($t) => trim($t))
        ->filter()
        ->values()
        ->all();
    $initialJoined = implode(',', $initial);
@endphp

{{--
    Tag input freeform (badge/chip). Pengguna mengetik lalu Enter atau koma untuk
    membuat tag baru — TANPA perlu terdaftar di DB lebih dulu (service meng-upsert
    nama→id per company).

    Nilai terkirim lewat SATU hidden input berisi nama tag dipisah koma
    (name="{{ $name }}"). Server (StoreArticleRequest::normalizeTags) memecahnya
    kembali menjadi array, sehingga tidak ada tag yang hilang saat submit.
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
        {{-- Chip tag (tampilan saja; nilai aktual di hidden input di bawah) --}}
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 bg-[#C59B27]/10 text-[#8a6d15] text-[11px] font-semibold px-2 py-1 rounded-lg border border-[#C59B27]/30">
                <span x-text="tag"></span>
                <button type="button" @click="remove(index)" class="text-[#8a6d15]/70 hover:text-rose-500 leading-none text-sm">&times;</button>
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

    {{-- Nilai terkirim: satu hidden input, nilainya diset sinkron lewat sync()
        agar tag terakhir yang diketik TIDAK pernah tertinggal saat form submit. --}}
    <input type="hidden" name="{{ $name }}" x-ref="tagsInput" value="{{ $initialJoined }}">

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

                    init() {
                        this.sync();
                    },

                    // Pecah draft (juga menangani paste "a, b, c"), trim tiap bagian,
                    // buang kosong, lalu tambahkan tanpa duplikat (case-insensitive).
                    commit() {
                        const parts = this.draft
                            .split(',')
                            .map(s => s.trim())
                            .filter(s => s !== '');
                        parts.forEach(value => {
                            const exists = this.tags.some(t => t.toLowerCase() === value.toLowerCase());
                            if (!exists) { this.tags.push(value); }
                        });
                        this.draft = '';
                        this.sync();
                    },

                    remove(index) {
                        this.tags.splice(index, 1);
                        this.sync();
                    },

                    removeLast() {
                        if (this.tags.length) { this.tags.pop(); }
                        this.sync();
                    },

                    // Tulis nilai sinkron ke hidden input — bebas dari race re-render
                    // Alpine, jadi semua tag pasti ikut terkirim.
                    sync() {
                        if (this.$refs.tagsInput) {
                            this.$refs.tagsInput.value = this.tags.join(',');
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
