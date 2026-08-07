<div class="space-y-6" x-data="{
    name: '{{ old('name', $category->name ?? '') }}',
    slug: '{{ old('slug', $category->slug ?? '') }}',
    isEdit: {{ isset($category) ? 'true' : 'false' }},
    slugify(value) {
        return value.toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    },
    init() {
        this.$watch('name', (value) => {
            this.slug = this.slugify(value);
        });
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-5">
            <x-input name="name" label="Nama Kategori" placeholder="Contoh: SNI, AI, Edukasi, dll." x-model="name"
                required />

            <div>
                <x-input name="slug_preview" label="Slug" placeholder="Auto-generated (misal: sni)" x-model="slug"
                    disabled class="bg-slate-100 text-slate-500 cursor-not-allowed select-none border-slate-200" />
                <input type="hidden" name="slug" :value="slug">
            </div>
        </div>

        <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">

            @if (auth()->user()->hasRole('super_admin'))
                <x-select name="company_id" label="Perusahaan" :options="$companies->pluck('name', 'id')" :value="old('company_id', $category->company_id ?? '')"
                    placeholder="Pilih Perusahaan..." searchable required />
            @else
                @php
                    $activeCompany =
                        $companies->firstWhere(
                            'id',
                            old(
                                'company_id',
                                $category->company_id ?? (auth()->user()->company_id ?? session('active_company_id')),
                            ),
                        ) ?? $companies->first();
                @endphp
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider select-none">Perusahaan</label>
                    <input type="text" value="{{ $activeCompany->name ?? 'Perusahaan Aktif' }}"
                        class="w-full bg-slate-100 text-slate-500 text-xs border border-slate-200/80 rounded-xl px-3 py-2.5 cursor-not-allowed select-none"
                        disabled readonly>
                    <input type="hidden" name="company_id" value="{{ $activeCompany->id ?? '' }}">
                </div>
            @endif
        </div>
    </div>

    <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
        <a href="{{ route('categories.index') }}"
            class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-lg transition">Batal</a>
        <button type="submit"
            class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition">
            {{ isset($category) ? 'Perbarui Kategori' : 'Simpan Kategori' }}
        </button>
    </div>
</div>
