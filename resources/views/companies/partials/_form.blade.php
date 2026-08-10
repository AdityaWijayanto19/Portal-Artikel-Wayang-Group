<div class="space-y-6" x-data="{
        name: @js(old('name', $company->name ?? '')),
        slug: @js(old('slug', $company->slug ?? '')),
        primaryColor: @js(old('primary_color', $company->primary_color ?? '#C59B27')),
        sidebarColor: @js(old('sidebar_color', $company->sidebar_color ?? '#1E1E1E')),
        infoOpen: true,
        themeOpen: true,
        slugify(text) {
            return text.toString().toLowerCase()
                .trim()
                .replace(/\s+/g, '-')           // Ganti spasi dengan -
                .replace(/[^\w\-]+/g, '')        // Hapus karakter non-word
                .replace(/\-\-+/g, '-');         // Ganti ganda - dengan single -
        },
        // Cerminan Client-side dari App\Helpers\ColorHelper (ITU-R BT.601 YIQ)
        contrast(hex) {
            let h = (hex || '').replace('#', '');
            if (h.length === 3 && /^[0-9a-fA-F]{3}$/.test(h)) {
                h = h.split('').map(c => c + c).join('');
            }
            if (!/^[0-9a-fA-F]{6}$/.test(h)) return '#1E1E1E';
            const r = parseInt(h.substr(0, 2), 16);
            const g = parseInt(h.substr(2, 2), 16);
            const b = parseInt(h.substr(4, 2), 16);
            const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
            return yiq >= 150 ? '#1E1E1E' : '#FFFFFF';
        }
     }">

    {{-- ============ ACCORDION 1: INFORMASI PERUSAHAAN ============ --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <button type="button" @click="infoOpen = !infoOpen"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H7m4 0v10" />
                </svg>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Perusahaan</span>
            </span>
            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="infoOpen ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="infoOpen" x-collapse class="px-5 pb-5 border-t border-slate-100 pt-5">
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
                            class="w-4 h-4 rounded border-slate-300 text-brand accent-brand focus:ring-brand cursor-pointer transition"
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
    </div>

    {{-- ============ ACCORDION 2: KONFIGURASI TEMA & BRANDING ============ --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <button type="button" @click="themeOpen = !themeOpen"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                </svg>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Konfigurasi Tema &amp; Branding
                </span>
                {{-- Swatch ringkas warna aktif --}}
                <span class="flex items-center gap-1 ml-1">
                    <span class="w-3.5 h-3.5 rounded-full border border-slate-200 shadow-sm"
                        :style="{ backgroundColor: primaryColor }"></span>
                    <span class="w-3.5 h-3.5 rounded-full border border-slate-200 shadow-sm"
                        :style="{ backgroundColor: sidebarColor }"></span>
                </span>
            </span>
            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="themeOpen ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div x-show="themeOpen" x-collapse class="px-5 pb-5 border-t border-slate-100 pt-5">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

                <!-- Kolom Kiri: Input Warna -->
                <div class="space-y-5">
                    {{-- Color Picker: Primary --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Warna Primer (Tombol &amp; Aksen) <span class="text-brand">*</span>
                        </label>
                        <div class="flex items-center gap-2.5">
                            <input type="color" x-model="primaryColor"
                                class="w-11 h-11 rounded-lg border border-slate-200 bg-white cursor-pointer p-1 shadow-sm">
                            <input type="text" x-model="primaryColor"
                                placeholder="#C59B27"
                                pattern="^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$"
                                maxlength="7"
                                class="w-full bg-white text-slate-800 text-xs font-mono border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs uppercase">
                            <input type="hidden" name="primary_color" :value="primaryColor">
                        </div>
                        <p class="text-[10px] text-slate-400">Gunakan format hex, contoh: <code>#C59B27</code></p>
                        @error('primary_color')
                            <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Color Picker: Sidebar --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Warna Sidebar (Background Menu) <span class="text-brand">*</span>
                        </label>
                        <div class="flex items-center gap-2.5">
                            <input type="color" x-model="sidebarColor"
                                class="w-11 h-11 rounded-lg border border-slate-200 bg-white cursor-pointer p-1 shadow-sm">
                            <input type="text" x-model="sidebarColor"
                                placeholder="#1E1E1E"
                                pattern="^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$"
                                maxlength="7"
                                class="w-full bg-white text-slate-800 text-xs font-mono border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs uppercase">
                            <input type="hidden" name="sidebar_color" :value="sidebarColor">
                        </div>
                        <p class="text-[10px] text-slate-400">Gunakan format hex, contoh: <code>#1E1E1E</code></p>
                        @error('sidebar_color')
                            <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Kolom Kanan: Live Preview Miniatur --}}
                <div class="space-y-4">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Live Preview
                    </label>

                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-4">
                        <div class="flex gap-3">
                            {{-- Mini Sidebar --}}
                            <div class="w-24 shrink-0 rounded-xl p-2.5 space-y-2.5 text-[8px] transition-colors duration-200"
                                :style="{ backgroundColor: sidebarColor, color: contrast(sidebarColor) }">
                                <div class="flex items-center gap-1.5 pb-1.5 border-b"
                                    :style="{ borderColor: contrast(sidebarColor) + '33' }">
                                    <span class="w-4 h-4 rounded-md flex items-center justify-center font-bold text-[7px]"
                                        :style="{ backgroundColor: primaryColor, color: contrast(primaryColor) }">
                                        {{ strtoupper(substr(old('name', $company->name ?? 'PT'), 0, 2)) }}
                                    </span>
                                    <span class="font-semibold truncate">Menu</span>
                                </div>
                                <div class="h-3 rounded-md transition-colors duration-200"
                                    :style="{ backgroundColor: primaryColor, color: contrast(primaryColor) }"></div>
                                <div class="h-2 rounded"
                                    :style="{ backgroundColor: contrast(sidebarColor) + '26' }"></div>
                                <div class="h-2 rounded"
                                    :style="{ backgroundColor: contrast(sidebarColor) + '26' }"></div>
                                <div class="h-2 rounded"
                                    :style="{ backgroundColor: contrast(sidebarColor) + '26' }"></div>
                            </div>

                            {{-- Mini Konten + Tombol --}}
                            <div class="flex-1 space-y-2.5">
                                <div class="h-8 bg-white border border-slate-200 rounded-lg flex items-center px-2 gap-2">
                                    <span class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: primaryColor }"></span>
                                    <span class="w-16 h-1.5 rounded bg-slate-200"></span>
                                </div>
                                <div class="space-y-1.5 bg-white border border-slate-200 rounded-lg p-2.5">
                                    <div class="w-3/4 h-2 rounded bg-slate-200"></div>
                                    <div class="w-full h-2 rounded bg-slate-100"></div>
                                    <div class="w-5/6 h-2 rounded bg-slate-100"></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center gap-1 text-[8px] font-semibold px-2.5 py-1.5 rounded-lg transition-colors duration-200"
                                        :style="{ backgroundColor: primaryColor, color: contrast(primaryColor) }">
                                        Simpan Perubahan
                                    </span>
                                    <span class="text-[8px] text-slate-400"
                                        x-text="primaryColor + ' / ' + sidebarColor"></span>
                                </div>
                            </div>
                        </div>

                        <p class="text-[10px] text-slate-400 leading-relaxed">
                            Pratinjau otomatis berubah real-time. Warna teks dihitung otomatis (kontras) agar tetap
                            terbaca.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>