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
            if (!this.isEdit || !this.slug) {
                this.slug = this.slugify(value);
            }
        });
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kolom Kiri: Input Utama --}}
        <div class="space-y-5">
            <x-input name="name" label="Nama Kategori" placeholder="Contoh: SNI, AI, Edukasi, dll." x-model="name"
                required />

            <div>
                <x-input name="slug_preview" label="Slug" placeholder="Auto-generated (misal: sni)" x-model="slug"
                    class="bg-slate-100 text-slate-500 cursor-not-allowed select-none border-slate-200" disabled />
                {{-- Slug asli yang dikirim ke Backend --}}
                <input type="hidden" name="slug" :value="slug">
            </div>
        </div>

        {{-- Kolom Kanan: Tenant / Perusahaan Context --}}
        <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
            @if (auth()->user()->hasRole('super_admin'))
                <x-select name="company_id" label="Perusahaan" :options="$companies->pluck('name', 'id')" :value="old('company_id', $category->company_id ?? '')"
                    placeholder="Pilih Perusahaan..." searchable required />
            @else
                @php
                    $activeCompanyId = old(
                        'company_id',
                        $category->company_id ?? (auth()->user()->company_id ?? session('active_company_id')),
                    );
                    $activeCompany = $companies->firstWhere('id', $activeCompanyId) ?? $companies->first();
                @endphp
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5 select-none">
                        Perusahaan
                    </label>
                    <input type="text" value="{{ $activeCompany->name ?? 'Perusahaan Aktif' }}"
                        class="w-full bg-slate-100 text-slate-500 text-xs border border-slate-200/80 rounded-xl px-3 py-2.5 cursor-not-allowed select-none"
                        disabled readonly>
                    <input type="hidden" name="company_id" value="{{ $activeCompany->id ?? '' }}">
                </div>
            @endif
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
        <x-button href="{{ route('categories.index') }}" variant="secondary">
            Batal
        </x-button>
        <x-button type="submit" variant="primary">
            {{ isset($category) ? 'Perbarui Kategori' : 'Simpan Kategori' }}
        </x-button>
    </div>
</div>
