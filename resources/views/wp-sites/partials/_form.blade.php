<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6"
     x-data="{
        isSuperAdmin: {{ auth()->user()->hasRole('super_admin') ? 'true' : 'false' }},
        selectedCompanyId: '{{ old('company_id', isset($wpSite) ? ($wpSite->company_id ?? '') : '') }}',
        selectedCategoryIds: @js(array_map('strval', old('category_ids', isset($wpSite) ? $wpSite->categories->pluck('id')->all() : []))),
        allCategories: @js($allCategories->map(fn ($category) => [
            'id' => (string) $category->id,
            'name' => $category->name,
            'company_id' => (string) $category->company_id,
        ])),
        get filteredCategories() {
            return this.allCategories.filter((category) => String(category.company_id) === String(this.selectedCompanyId));
        },
        onCompanyChange(event) {
            const value = event.detail ?? event.target?.value;
            if (value !== undefined && value !== null && value !== '') {
                this.selectedCompanyId = String(value);
            }
        },
        init() {
            if (! this.selectedCompanyId && ! this.isSuperAdmin) {
                this.selectedCompanyId = '{{ auth()->user()->company_id ?? '' }}';
            }

            this.$watch('selectedCompanyId', () => {
                this.selectedCategoryIds = this.selectedCategoryIds.filter((categoryId) =>
                    this.filteredCategories.some((category) => String(category.id) === String(categoryId))
                );
            });
        }
     }">
    @if($errors->any())
        <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-xl shadow-sm">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-rose-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.29 3.86l-8.4 14.52A2 2 0 003.62 21h16.76a2 2 0 001.73-2.62l-8.4-14.52a2 2 0 00-3.42 0z"/></svg>
                <div class="space-y-1">
                    <div class="font-semibold">Ada input yang belum benar.</div>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <div class="lg:col-span-7 space-y-5">
                <x-input name="site_name" label="Nama Situs" placeholder="mis. Website Perusahaan A"
                    value="{{ old('site_name', $wpSite->site_name ?? '') }}" required />

                <x-input type="url" name="site_url" label="URL Situs" placeholder="https://example.com"
                    value="{{ old('site_url', $wpSite->site_url ?? '') }}" required />

                <x-input name="wp_username" label="Username WordPress" placeholder="mis. admin"
                    value="{{ old('wp_username', $wpSite->wp_username ?? '') }}" required />

                <div>
                    <x-input type="password" name="wp_app_password" label="Application Password"
                        placeholder="xxxx xxxx xxxx xxxx xxxx xxxx"
                        value="{{ old('wp_app_password', $wpSite->wp_app_password ?? '') }}" required />
                    <p class="mt-1 text-[11px] text-slate-500">Gunakan <span class="font-semibold">Application Password</span> WordPress (format <code>xxxx xxxx xxxx xxxx xxxx xxxx</code>), bukan password login.</p>
                </div>
            </div>

            <div class="lg:col-span-5 bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                @if(auth()->user()->hasRole('super_admin'))
                    <div @change="onCompanyChange($event)">
                        <x-select name="company_id" label="Perusahaan" :options="$companies->pluck('name', 'id')"
                            :value="old('company_id', $wpSite->company_id ?? '')" placeholder="Pilih Perusahaan..."
                            searchable required />
                    </div>
                @else
                    @php
                        $activeCompany = $companies->firstWhere('id', auth()->user()->company_id ?? session('active_company_id')) ?? $companies->first();
                    @endphp
                    <x-input name="company_display" label="Perusahaan / Tenant"
                        :value="$activeCompany->name ?? 'Perusahaan Aktif'" disabled />
                    <input type="hidden" name="company_id" value="{{ $activeCompany->id ?? auth()->user()->company_id }}">
                @endif

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kategori Perusahaan</label>

                    <div class="max-h-56 overflow-y-auto rounded-xl border border-slate-200/80 bg-white p-3 space-y-2">
                        <template x-for="category in filteredCategories" :key="category.id">
                            <label class="flex items-center gap-3 rounded-lg border border-slate-200/70 px-3 py-2 hover:border-[#C59B27] transition cursor-pointer">
                                <input type="checkbox" name="category_ids[]" :value="category.id" x-model="selectedCategoryIds" class="rounded border-slate-300 text-[#C59B27] focus:ring-[#C59B27]">
                                <span class="text-xs text-slate-700 font-semibold" x-text="category.name"></span>
                            </label>
                        </template>

                        <div x-show="filteredCategories.length === 0" class="text-xs text-rose-500 py-2">
                            Belum ada kategori untuk perusahaan ini.
                        </div>
                    </div>

                    <p class="mt-1 text-[11px] text-slate-500">Pilih satu atau lebih kategori. Sistem akan menolak jika perusahaan belum punya kategori.</p>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
            <x-button href="{{ route('wp-sites.index') }}" variant="secondary">Batal</x-button>
            <x-button type="submit" variant="primary">{{ isset($wpSite) ? 'Perbarui WP Site' : 'Simpan WP Site' }}</x-button>
        </div>
    </div>
</div>
