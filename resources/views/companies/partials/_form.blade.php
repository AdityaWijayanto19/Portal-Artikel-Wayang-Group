<div class="space-y-6"
     x-data="{
        name: '{{ old('name', $company->name ?? '') }}',
        slug: '{{ old('slug', $company->slug ?? '') }}',
        slugify(text) {
            return text.toString().toLowerCase()
                .trim()
                .replace(/\s+/g, '-')           // Ganti spasi dengan -
                .replace(/[^\w\-]+/g, '')       // Hapus karakter non-word
                .replace(/\-\-+/g, '-');        // Ganti ganda - dengan single -
        }
     }">

    <!-- Grid 2 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Kolom Kiri: Form Fields & Action Buttons -->
        <div class="lg:col-span-7 space-y-5">
            <!-- Nama Perusahaan -->
            <x-input
                name="name"
                label="Nama Perusahaan"
                placeholder="Contoh: PT Wayang Logistics Digital"
                x-model="name"
                @input="slug = slugify(name)"
                required
            />

            <!-- Slug (Disabled / Muted + Hidden Field untuk Form Submit) -->
            <div>
                <x-input
                    name="slug_preview"
                    label="Slug"
                    placeholder="Auto-generated (misal: pt-wayang-logistics-digital)"
                    x-model="slug"
                    disabled
                    class="bg-slate-100 text-slate-500 cursor-not-allowed select-none border-slate-200"
                />
                {{-- Field tersembunyi agar nilai slug tetap terkirim saat form disubmit --}}
                <input type="hidden" name="slug" :value="slug">
            </div>

            <!-- Checkbox Status -->
            <div class="flex items-center gap-2 pt-1">
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', $company->is_active ?? true) ? 'checked' : '' }}
                    class="w-4 h-4 rounded border-slate-300 text-[#C59B27] accent-[#C59B27] focus:ring-[#C59B27] cursor-pointer transition"
                >
                <label for="is_active" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                    Status Perusahaan Aktif
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
                <x-button href="{{ route('companies.index') }}" variant="secondary">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    {{ isset($company) ? 'Perbarui Perusahaan' : 'Simpan Perusahaan' }}
                </x-button>
            </div>
        </div>

        <!-- Kolom Kanan: Dropzone Image -->
        <div class="lg:col-span-5 flex flex-col">
            <x-dropzone
                name="logo"
                label="Logo Perusahaan"
                :previewUrl="isset($company) && $company->logo_path ? asset('storage/' . $company->logo_path) : null"
            />
        </div>

    </div>
</div>
