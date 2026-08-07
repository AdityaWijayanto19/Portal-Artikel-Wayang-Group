<div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6"
     x-data="{
        isSuperAdmin: {{ auth()->user()->hasRole('super_admin') ? 'true' : 'false' }},
        selectedCompanyId: '{{ old('company_id', $selectedCompanyId ?? ($wpSite->company_id ?? '')) }}',
        selectedCategoryIds: @js(array_map('strval', old('category_ids', isset($wpSite) ? $wpSite->categories->pluck('id')->all() : []))),
        allCategories: @js($allCategories->map(fn ($category) => [
            'id' => (string) $category->id,
            'name' => $category->name,
            'company_id' => (string) $category->company_id,
        ])),
        get filteredCategories() {
            return this.allCategories.filter((category) => String(category.company_id) === String(this.selectedCompanyId));
        },
        init() {
            if (! this.selectedCompanyId && ! this.isSuperAdmin) {
                this.selectedCompanyId = '{{ $selectedCompanyId ?? auth()->user()->company_id }}';
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-5">
                <div>
                    <label for="site_name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Situs</label>
                    <input id="site_name" name="site_name" type="text" value="{{ old('site_name', $wpSite->site_name ?? '') }}" required class="w-full bg-white text-slate-800 text-xs border {{ $errors->has('site_name') ? 'border-rose-300 focus:border-rose-500' : 'border-slate-200/80 focus:border-[#C59B27]' }} rounded-xl px-3 py-2.5 focus:outline-none transition">
                </div>

                <div>
                    <label for="site_url" class="block text-xs font-semibold text-slate-700 mb-1.5">URL Situs</label>
                    <input id="site_url" name="site_url" type="url" value="{{ old('site_url', $wpSite->site_url ?? '') }}" required class="w-full bg-white text-slate-800 text-xs border {{ $errors->has('site_url') ? 'border-rose-300 focus:border-rose-500' : 'border-slate-200/80 focus:border-[#C59B27]' }} rounded-xl px-3 py-2.5 focus:outline-none transition">
                </div>

                <div>
                    <label for="wp_username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username WordPress</label>
                    <input id="wp_username" name="wp_username" type="text" value="{{ old('wp_username', $wpSite->wp_username ?? '') }}" required class="w-full bg-white text-slate-800 text-xs border {{ $errors->has('wp_username') ? 'border-rose-300 focus:border-rose-500' : 'border-slate-200/80 focus:border-[#C59B27]' }} rounded-xl px-3 py-2.5 focus:outline-none transition">
                </div>

                <div>
                    <label for="wp_app_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Application Password</label>
                    <input id="wp_app_password" name="wp_app_password" type="password" value="{{ old('wp_app_password', $wpSite->wp_app_password ?? '') }}" required class="w-full bg-white text-slate-800 text-xs border {{ $errors->has('wp_app_password') ? 'border-rose-300 focus:border-rose-500' : 'border-slate-200/80 focus:border-[#C59B27]' }} rounded-xl px-3 py-2.5 focus:outline-none transition">
                    <p class="mt-1 text-[11px] text-slate-500">Gunakan <span class="font-semibold">Application Password</span> WordPress (format <code>xxxx xxxx xxxx xxxx xxxx xxxx</code>), bukan password login.</p>
                </div>
            </div>

            <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                @if(auth()->user()->hasRole('super_admin'))
                <x-select name="company_id" label="Perusahaan" :options="$companies->pluck('name', 'id')" :value="old('company_id', $wpSite->company_id ?? '')"
                    placeholder="Pilih Perusahaan..." searchable required />
                @else
                    @php $activeCompany = $companies->firstWhere('id', auth()->user()->company_id ?? session('active_company_id')) ?? $companies->first(); @endphp
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Perusahaan / Tenant</label>
                        <input type="text" value="{{ $activeCompany->name ?? 'Perusahaan Aktif' }}" class="w-full bg-slate-100 text-slate-500 text-xs border border-slate-200/80 rounded-xl px-3 py-2.5 cursor-not-allowed select-none" disabled readonly>
                        <input type="hidden" name="company_id" value="{{ $activeCompany->id ?? auth()->user()->company_id }}">
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori yang Sinkron dengan Perusahaan</label>

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
            <a href="{{ route('wp-sites.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-lg transition">Batal</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition">{{ isset($wpSite) ? 'Perbarui WP Site' : 'Simpan WP Site' }}</button>
        </div>
    </div>
</div>
