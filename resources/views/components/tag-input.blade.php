@props([
    'name' => 'tags',
    'label' => 'Tag',
    'value' => [],
    'placeholder' => 'Ketik tag lalu tekan Enter atau tanda #...',
])

@php
    $initial = old($name, $value);
    if ($initial instanceof \Illuminate\Support\Collection) {
        $initial = $initial->pluck('name')->all();
    }
    if (is_string($initial)) {
        // Splitting hanya berdasarkan enter atau hashtag
        $initial = preg_split('/[\r\n#]+/', $initial);
    }
    $initial = collect($initial ?? [])
        ->map(fn ($t) => is_object($t) ? ($t->name ?? '') : (string) $t)
        ->map(fn ($t) => trim($t))
        ->map(fn ($t) => ltrim($t, '#')) // Hapus hashtag jika ada
        ->filter()
        ->values()
        ->all();
    $initialJoined = implode(',', $initial);
@endphp

<div class="space-y-1.5" x-data="tagInput(@js($initial))">
    @if ($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <div
        class="w-full min-h-[44px] bg-white border border-slate-300 rounded-xl px-2.5 py-2 flex flex-wrap gap-1.5 items-center focus-within:border-brand focus-within:ring-1 focus-within:ring-brand transition shadow-xs"
        @click="$refs.tagField.focus()"
    >
        {{-- Chip tag (otomatis tampilkan # di depan badge agar rapi) --}}
        <template x-for="(tag, index) in tags" :key="index">
            <span class="inline-flex items-center gap-1 bg-brand/10 text-brand/80 text-[11px] font-semibold px-2 py-1 rounded-lg border border-brand/30">
                <span x-text="'#' + tag"></span>
                <button type="button" @click="remove(index)" class="text-brand/70 hover:text-rose-500 leading-none text-sm">&times;</button>
            </span>
        </template>

        {{-- Field ketik --}}
        <input
            type="text"
            x-ref="tagField"
            x-model="draft"
            @input="handleInput()"
            @keydown.enter.prevent="commit()"
            @keydown.backspace="draft === '' && removeLast()"
            @blur="commit()"
            placeholder="{{ $placeholder }}"
            class="flex-1 min-w-[120px] bg-transparent text-xs text-slate-800 focus:outline-none placeholder:text-slate-400 py-1"
        >
    </div>

    <input type="hidden" name="{{ $name }}" x-ref="tagsInput" value="{{ $initialJoined }}">

    <p class="text-[11px] text-slate-400">Pisahkan dengan Enter atau tanda hashtag (#).</p>

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

                    // Deteksi jika input memuat enter atau tanda #
                    handleInput() {
                        if (/[\r\n#]/.test(this.draft)) {
                            this.commit();
                        }
                    },

                    // Break kata berdasarkan enter atau hashtag (#)
                    commit() {
                        const parts = this.draft
                            .split(/[\r\n#]+/)
                            .map(s => s.trim())
                            .map(s => s.replace(/^#+/, '')) // Buang tanda # di depan kata kalau terbawa
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

                    sync() {
                        if (this.$refs.tagsInput) {
                            this.$refs.tagsInput.value = this.tags.join('\n');
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
