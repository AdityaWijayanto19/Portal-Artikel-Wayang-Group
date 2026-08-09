@php
    $isEdit = isset($article);
    $selectedCategories = old('categories', $isEdit ? $article->categories->pluck('id')->all() : []);
    $selectedTagNames = old('tags', $isEdit ? $article->tags->pluck('name')->all() : []);
    $selectedSites = old('wp_site_ids', $isEdit ? $article->sitePublications->pluck('wp_site_id')->all() : []);
    $seo = $isEdit ? $article->seoMeta : null;
    $selectedAuthor = old('user_id', $isEdit ? $article->user_id : auth()->id());
    $selectedStatus = old(
        'status',
        $isEdit && in_array($article->status, ['published', 'queued']) ? 'published' : 'draft',
    );
    $statusOptions = [
        ['value' => 'draft', 'label' => 'Draft (simpan saja)'],
        ['value' => 'published', 'label' => 'Published (kirim ke WordPress)'],
    ];
@endphp

<div x-data="articleEditor({
    content: @js(old('content', $isEdit ? $article->content : '')),
    articleTitle: @js(old('title', $isEdit ? $article->title : '')),
    seoTitle: @js(old('yoast_title', $seo->yoast_title ?? '')),
    slug: @js(old('slug', $isEdit ? $article->slug : '')),
    metadesc: @js(old('yoast_metadesc', $seo->yoast_metadesc ?? '')),
    focuskw: @js(old('yoast_focuskw', $seo->yoast_focuskw ?? '')),
    altText: @js(old('image_alt_text', $isEdit ? $article->image_alt_text : '')),
    status: @js($selectedStatus),
})" @content-updated="onContentChange($event)"
    class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

    {{-- ================= KOLOM KIRI: KONTEN ================= --}}
    <div class="lg:col-span-8 space-y-5">

        {{-- Judul Artikel (menjadi <h1> di WordPress) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-2">
            <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Judul Artikel <span class="text-rose-500">*</span>
            </label>
            <input type="text" name="title" id="title" x-model="articleTitle" @input="onTitleInput()"
                placeholder="Judul utama yang menarik & mengandung keyword..."
                class="w-full bg-white text-slate-900 text-xl font-bold border-0 border-b border-slate-200 rounded-none px-1 py-2 focus:outline-none focus:border-[#C59B27] transition placeholder:text-slate-300 placeholder:font-normal">
            <p class="text-[10px] text-slate-400">Judul ini akan menjadi <strong>&lt;h1&gt;</strong> pada artikel
                WordPress.</p>
            @error('title')
                <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Smart Rich Text Editor (TinyMCE) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <x-rich-editor name="content" label="Isi Artikel" required :value="old('content', $isEdit ? $article->content : '')" />
            <p class="text-[10px] text-slate-400 mt-2">
                Tempel dari Word/Google Docs aman — format dibersihkan otomatis. Gunakan
                <strong>Sub-judul (H2)</strong> untuk struktur SEO yang baik.
            </p>
        </div>

        {{-- ============ ACCORDION SEO ============ --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            {{-- Header accordion --}}
            <button type="button" @click="seoOpen = !seoOpen"
                class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-slate-50 transition">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#C59B27]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Optimasi SEO &amp; Google
                        Preview</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="scoreBadgeClass">
                        <span x-text="score"></span>/100
                    </span>
                </span>
                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="seoOpen ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="seoOpen" x-collapse class="px-5 pb-5 space-y-5 border-t border-slate-100 pt-5">

                {{-- Field SEO --}}
                <x-input name="yoast_focuskw" label="Focus Keyphrase" placeholder="mis. jasa logistik jakarta"
                    x-model="focuskw" @input="recompute()" />

                <div class="space-y-1.5">
                    <label for="yoast_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">SEO
                        Title</label>
                    <input type="text" name="yoast_title" id="yoast_title" x-model="seoTitle" @input="recompute()"
                        placeholder="Judul untuk mesin pencari (50-60 karakter ideal)"
                        class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition">
                    <p class="text-[10px] text-slate-400">Kosongkan untuk memakai Judul Artikel. Panjang: <span
                            x-text="(seoTitle || articleTitle).length"></span> karakter</p>
                </div>

                <div class="space-y-1.5">
                    <label for="slug_field" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Slug
                        (URL)</label>
                    <input type="text" id="slug_field" x-model="slug"
                        @input="slug = slugify(slug); slugEdited = true; recompute()" placeholder="auto-dari-judul"
                        class="w-full bg-white text-slate-800 text-xs font-mono border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition">
                    <input type="hidden" name="slug" :value="slug">
                </div>

                <div class="space-y-1.5">
                    <label for="yoast_metadesc"
                        class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Meta Description</label>
                    <textarea name="yoast_metadesc" id="yoast_metadesc" rows="3" x-model="metadesc" @input="recompute()"
                        placeholder="Ringkasan menarik 120-156 karakter yang muncul di hasil pencarian Google..."
                        class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition resize-y"></textarea>
                    <p class="text-[10px] text-slate-400">Panjang: <span x-text="metadesc.length"></span> karakter</p>
                </div>

                {{-- ===== Google SERP Preview ===== --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Google
                            Preview</label>
                        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 bg-slate-50">
                            <button type="button" @click="serpDevice = 'desktop'"
                                :class="serpDevice === 'desktop' ? 'bg-white shadow-sm text-slate-700' : 'text-slate-400'"
                                class="px-2.5 py-1 text-[10px] font-semibold rounded-md transition">Desktop</button>
                            <button type="button" @click="serpDevice = 'mobile'"
                                :class="serpDevice === 'mobile' ? 'bg-white shadow-sm text-slate-700' : 'text-slate-400'"
                                class="px-2.5 py-1 text-[10px] font-semibold rounded-md transition">Mobile</button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-xl p-4 bg-white"
                        :class="serpDevice === 'mobile' ? 'max-w-sm' : ''">
                        {{-- Baris URL / breadcrumb --}}
                        <div class="flex items-center gap-2 mb-1">
                            <span
                                class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-500 shrink-0">W</span>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-700 leading-tight truncate">Wayang Group</p>
                                <p class="text-[11px] text-emerald-700 leading-tight truncate" x-text="serpUrl"></p>
                            </div>
                        </div>
                        {{-- Judul biru --}}
                        <p class="text-[#1a0dab] text-lg leading-snug hover:underline cursor-pointer truncate"
                            x-text="serpTitle"></p>
                        {{-- Deskripsi --}}
                        <p class="text-[13px] text-slate-600 leading-snug mt-0.5" x-text="serpDesc"></p>
                    </div>
                </div>

                {{-- ===== Sub-seksi: Analisis SEO ===== --}}
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <button type="button" @click="analysisOpen = !analysisOpen"
                        class="w-full flex items-center justify-between px-4 py-3 text-left bg-slate-50 hover:bg-slate-100 transition">
                        <span class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">Analisis
                                SEO</span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="scoreBadgeClass"
                                x-text="scoreCategory"></span>
                        </span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform"
                            :class="analysisOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="analysisOpen" x-collapse class="p-4 space-y-4">
                        {{-- Gauge + gatekeeper --}}
                        <div class="flex items-center gap-4">
                            <div class="relative w-24 h-24 shrink-0">
                                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0"
                                        stroke-width="9" />
                                    <circle cx="50" cy="50" r="42" fill="none"
                                        :stroke="scoreStroke" stroke-width="9" stroke-linecap="round"
                                        :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * score / 100)"
                                        style="transition: stroke-dashoffset .5s ease, stroke .3s ease" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-xl font-black text-slate-800" x-text="score"></span>
                                    <span class="text-[9px] text-slate-400 uppercase">dari 100</span>
                                </div>
                            </div>
                            <div class="text-[11px] rounded-lg px-3 py-2 flex-1"
                                :class="score >= 80 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                <span x-show="score >= 80">Skor memenuhi syarat. Artikel siap dipublikasikan.</span>
                                <span x-show="score < 80">Skor minimal <strong>80</strong> untuk publish. Perbaiki
                                    indikator di bawah.</span>
                            </div>
                        </div>

                        {{-- Breakdown indikator --}}
                        <div class="space-y-1 max-h-56 overflow-y-auto pr-1">
                            <template x-for="item in breakdown" :key="item.label">
                                <div
                                    class="flex items-center justify-between text-[11px] py-1 border-b border-slate-50">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <svg class="w-3 h-3 shrink-0"
                                            :class="item.score >= item.max ? 'text-emerald-500' : (item.score > 0 ?
                                                'text-amber-500' : 'text-slate-300')"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-slate-600 truncate" x-text="item.label"></span>
                                    </div>
                                    <span class="font-semibold text-slate-500 shrink-0 ml-2">
                                        <span x-text="item.score"></span>/<span x-text="item.max"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KOLOM KANAN: PUBLIKASI & MEDIA ================= --}}
    <div class="lg:col-span-4 space-y-5 lg:sticky lg:top-4" x-data="siteLinker({
        selectedCategories: @js($selectedCategories),
        selectedSites: @js($selectedSites),
        siteCategories: @js($wpSites->map(fn($s) => ['id' => $s->id, 'categories' => $s->categories->pluck('id')->all()])->values()->all()),
    })">

        {{-- Aksi Publikasi (status + submit) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
            {{-- Status --}}
            <div class="space-y-1.5">
                <label for="status"
                    class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Status</label>
                <div class="relative w-full">
                    <select name="status" id="status" x-model="status"
                        class="w-full bg-white text-slate-800 text-xs font-medium border border-slate-300 rounded-xl px-3.5 py-2.5 pr-10 appearance-none focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition shadow-xs cursor-pointer select-none">
                        <option value="draft" class="text-slate-800 bg-white py-2">Draft (simpan saja)</option>
                        <option value="published" class="text-slate-800 bg-white py-2">Published (kirim ke WordPress)
                        </option>
                    </select>

                    {{-- Icon Panah Dropdown Kustom (Biar Presisi Sesuai Component) --}}
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
            </div>

            {{-- action diturunkan dari status; server tetap gatekeep skor SEO. --}}
            <input type="hidden" name="action" :value="status === 'published' ? 'publish' : 'draft'">

            <button type="submit" :disabled="status === 'published' && score < 80"
                :class="(status === 'published' && score < 80) ? 'opacity-50 cursor-not-allowed bg-slate-300' : (
                    status === 'published' ? 'bg-[#C59B27] hover:bg-[#b08820] text-white' :
                    'bg-slate-800 hover:bg-slate-900 text-white')"
                class="w-full inline-flex items-center justify-center gap-2 text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 10l7-7m0 0l7 7m-7-7v18" x-show="status === 'published'" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
                        x-show="status !== 'published'" />
                </svg>
                <span x-show="status !== 'published'">Simpan Draft</span>
                <span x-show="status === 'published' && score >= 80">Publish ke WordPress</span>
                <span x-show="status === 'published' && score < 80">Publish (SEO &lt; 80)</span>
            </button>
            <p class="text-[10px] text-slate-400 text-center leading-relaxed">
                Publikasi diverifikasi ulang di server. Skor SEO harus &ge; 80.
            </p>
        </div>

        {{-- Gambar Unggulan --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
            <x-dropzone name="featured_image" label="Gambar Unggulan" :previewUrl="$isEdit && $article->featured_image_path
                ? asset('storage/' . $article->featured_image_path)
                : null" />
            <x-input name="image_alt_text" label="Alt Text Gambar" placeholder="Deskripsi gambar untuk SEO"
                x-model="altText" @input="recompute()" />
        </div>

        {{-- Author (sinkron ke user WordPress) --}}
        @php
            $canChooseAuthor = auth()->user()?->isSuperAdmin() || auth()->user()?->isAdmin();
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-2">
            <label for="user_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Author <span class="text-rose-500">*</span>
            </label>

            @if ($canChooseAuthor)
                <select name="user_id" id="user_id"
                    class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition">
                    @forelse ($authors as $author)
                        <option value="{{ $author->id }}"
                            {{ (int) $selectedAuthor === $author->id ? 'selected' : '' }}>
                            {{ $author->name }}@if (!empty($author->username))
                                &middot; {{ $author->username }}
                            @endif
                        </option>
                    @empty
                        <option value="{{ auth()->id() }}" selected>{{ auth()->user()->name }}</option>
                    @endforelse
                </select>
                <p class="text-[10px] text-slate-400">Artikel dipublish atas nama author ini di WordPress (bukan user
                    default).</p>
            @else
                {{-- Role Author: terkunci ke akun yang sedang login. --}}
                <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                <div
                    class="w-full flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs text-slate-500">
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>{{ auth()->user()->name }}</span>
                </div>
                <p class="text-[10px] text-slate-400">Author terkunci ke akun Anda. Hubungi admin untuk mengubah
                    author.</p>
            @endif

            @error('user_id')
                <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Kategori (terisolasi per perusahaan aktif) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Kategori <span class="text-rose-500">*</span>
            </label>
            <div class="max-h-48 overflow-y-auto space-y-2 pr-1">
                @forelse ($categories as $category)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                            :checked="selectedCategories.includes('{{ $category->id }}')"
                            @change="onCategoryToggle('{{ $category->id }}', $el.checked)"
                            class="w-4 h-4 rounded border-slate-300 text-[#C59B27] accent-[#C59B27] focus:ring-[#C59B27]">
                        <span class="text-xs text-slate-700">{{ $category->name }}</span>
                    </label>
                @empty
                    <p class="text-[11px] text-slate-400">Belum ada kategori untuk perusahaan ini.</p>
                @endforelse
            </div>
            @error('categories')
                <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Situs WordPress Target --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                Situs WordPress <span class="text-rose-500">*</span>
            </label>
            <p class="text-[10px] text-slate-400 -mt-1">Artikel akan dipublikasikan ke situs terpilih.</p>
            @forelse ($wpSites as $site)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="wp_site_ids[]" value="{{ $site->id }}"
                        :checked="selectedSites.includes('{{ $site->id }}')"
                        @change="onSiteToggle('{{ $site->id }}', $el.checked)"
                        class="w-4 h-4 rounded border-slate-300 text-[#C59B27] accent-[#C59B27] focus:ring-[#C59B27]">
                    <span class="text-xs text-slate-700">{{ $site->site_name }}</span>
                </label>
            @empty
                <p class="text-[11px] text-slate-400">Belum ada situs WordPress untuk perusahaan ini.</p>
            @endforelse
            @error('wp_site_ids')
                <p class="text-[11px] text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        {{-- Tag (freeform, badge/chip) --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <x-tag-input name="tags" label="Tag" :value="$selectedTagNames" />
        </div>
    </div>
</div>

@push('scripts')
    <script>
        /**
         * Mesin SEO realtime — CERMINAN sisi klien dari SeoAnalyzerService (PHP).
         * Hanya untuk UX; gatekeeper sebenarnya tetap dihitung ulang di server.
         */
        function siteLinker(initial) {
            // Peta relasi kategori ↔ situs dari data server (tabel wp_site_category).
            const sitesForCategory = {}; // categoryId → [siteId]
            initial.siteCategories.forEach(s => {
                const siteId = String(s.id);
                s.categories.forEach(cid => {
                    (sitesForCategory[String(cid)] = sitesForCategory[String(cid)] || []).push(siteId);
                });
            });

            return {
                selectedCategories: [],
                selectedSites: [],
                // Override manual user terhadap auto-sync (objek dipakai sebagai Set).
                manualChecked: {},
                manualUnchecked: {},

                init() {
                    this.selectedCategories = (initial.selectedCategories || []).map(String);
                    const auto = this.autoSites();
                    (initial.selectedSites || []).forEach(id => {
                        const sid = String(id);
                        if (!auto.has(sid)) this.manualChecked[sid] = true;
                    });
                    this.recomputeSites();
                },

                // Situs yang tersinkron dengan kategori yang sedang tercentang.
                autoSites() {
                    const set = new Set();
                    this.selectedCategories.forEach(cid => {
                        (sitesForCategory[cid] || []).forEach(sid => set.add(sid));
                    });
                    return set;
                },

                // Rumus pilihan akhir:
                //   (auto dari kategori) + (manual checked) − (manual unchecked)
                recomputeSites() {
                    const next = new Set();
                    this.autoSites().forEach(sid => next.add(sid));
                    Object.keys(this.manualChecked).forEach(sid => next.add(sid));
                    Object.keys(this.manualUnchecked).forEach(sid => next.delete(sid));
                    this.selectedSites = [...next];
                },

                onCategoryToggle(categoryId, checked) {
                    const cid = String(categoryId);
                    if (checked) {
                        if (!this.selectedCategories.includes(cid)) this.selectedCategories.push(cid);
                    } else {
                        this.selectedCategories = this.selectedCategories.filter(c => c !== cid);
                    }
                    this.recomputeSites();
                },

                // Manual toggle langsung pada checkbox situs (tetap fleksibel).
                onSiteToggle(siteId, checked) {
                    const sid = String(siteId);
                    if (checked) {
                        this.manualChecked[sid] = true;
                        delete this.manualUnchecked[sid];
                    } else {
                        this.manualUnchecked[sid] = true;
                        delete this.manualChecked[sid];
                    }
                    this.recomputeSites();
                },
            };
        }

        /**
         * Mesin SEO realtime — CERMINAN sisi klien dari SeoAnalyzerService (PHP).
         * Hanya untuk UX; gatekeeper sebenarnya tetap dihitung ulang di server.
         */
        function articleEditor(initial) {
            return {
                content: initial.content || '',
                articleTitle: initial.articleTitle || '',
                seoTitle: initial.seoTitle || '',
                slug: initial.slug || '',
                metadesc: initial.metadesc || '',
                focuskw: initial.focuskw || '',
                altText: initial.altText || '',
                status: initial.status || 'draft',
                score: 0,
                breakdown: [],
                slugEdited: false,
                // State UI accordion & preview
                seoOpen: true,
                analysisOpen: true,
                serpDevice: 'desktop',

                init() {
                    this.slugEdited = !!this.slug;
                    if (!this.slug && this.articleTitle) this.slug = this.slugify(this.articleTitle);
                    this.recompute();
                },

                slugify(text) {
                    return text.toString().toLowerCase().trim()
                        .replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-');
                },

                onTitleInput() {
                    // Auto-slug hanya bila slug belum diedit manual.
                    if (!this.slugEdited) this.slug = this.slugify(this.articleTitle);
                    this.recompute();
                },

                onContentChange(e) {
                    // TinyMCE mengirim HTML bersih via event detail.
                    this.content = e.detail ?? this.content;
                    this.recompute();
                },

                // Judul efektif untuk SEO: pakai SEO Title bila diisi, jika tidak pakai Judul Artikel.
                get effectiveTitle() {
                    return (this.seoTitle || this.articleTitle || '').trim();
                },

                // ===== Google SERP Preview =====
                get serpTitle() {
                    const t = this.effectiveTitle || 'Judul Artikel Anda';
                    return t.length > 60 ? t.slice(0, 57) + '…' : t;
                },
                get serpUrl() {
                    const s = (this.slug || 'contoh-slug').trim();
                    return 'https://www.domainanda.com › ' + s;
                },
                get serpDesc() {
                    const d = (this.metadesc ||
                            'Isi Meta Description untuk mengontrol cuplikan yang tampil di hasil pencarian Google.')
                        .trim();
                    return d.length > 156 ? d.slice(0, 153) + '…' : d;
                },

                wordCount(html) {
                    const plain = html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    return plain === '' ? 0 : plain.split(' ').length;
                },

                recompute() {
                    const title = this.effectiveTitle;
                    const slug = (this.slug || '').trim();
                    const content = this.content || '';
                    const meta = (this.metadesc || '').trim();
                    const kw = (this.focuskw || '').trim().toLowerCase();
                    const alt = (this.altText || '').trim();
                    const lc = content.toLowerCase();
                    const words = this.wordCount(content);

                    const parts = [];
                    const tl = title.length;
                    parts.push({
                        label: 'SEO Title',
                        max: 15,
                        score: (tl >= 50 && tl <= 60) ? 15 : ((tl >= 35 && tl <= 75) ? 10 : (tl > 0 ? 5 : 0))
                    });

                    const ml = meta.length;
                    parts.push({
                        label: 'Meta Description',
                        max: 10,
                        score: (ml >= 120 && ml <= 156) ? 10 : ((ml >= 90 && ml <= 180) ? 6 : (ml > 0 ? 3 : 0))
                    });

                    let slugScore = 0;
                    if (slug.length) {
                        slugScore += (kw && slug.toLowerCase().includes(kw)) ? 6 : 3;
                        slugScore += /^[a-z0-9\-]+$/.test(slug) ? 4 : 0;
                    }
                    parts.push({
                        label: 'URL Slug',
                        max: 10,
                        score: Math.min(10, slugScore)
                    });

                    parts.push({
                        label: 'Focus Keyword',
                        max: 10,
                        score: kw !== '' ? 10 : 0
                    });
                    parts.push({
                        label: 'Keyword di Title',
                        max: 10,
                        score: (kw && title.toLowerCase().includes(kw)) ? 10 : 0
                    });
                    parts.push({
                        label: 'Keyword di Heading',
                        max: 10,
                        score: (kw && lc.includes(kw)) ? 10 : 0
                    });

                    const kwCount = kw ? (lc.split(kw).length - 1) : 0;
                    const density = (kwCount * 100) / Math.max(1, words);
                    parts.push({
                        label: 'Keyword Density',
                        max: 10,
                        score: (density >= 1 && density <= 2.5) ? 10 : (density > 0 ? 6 : 0)
                    });

                    const links = [...content.matchAll(/href=["']([^"']+)["']/gi)].map(m => m[1]);
                    parts.push({
                        label: 'Internal Link',
                        max: 10,
                        score: links.some(u => u.startsWith('/') || !u.startsWith('http')) ? 10 : 0
                    });
                    parts.push({
                        label: 'External Link',
                        max: 5,
                        score: links.some(u => u.startsWith('http')) ? 5 : 0
                    });
                    parts.push({
                        label: 'Alt Image',
                        max: 5,
                        score: alt !== '' ? 5 : 0
                    });
                    parts.push({
                        label: 'Content Length',
                        max: 10,
                        score: words >= 800 ? 10 : (words >= 500 ? 6 : (words > 0 ? 3 : 0))
                    });

                    const sentences = Math.max(1, (content.replace(/<[^>]+>/g, ' ').split(/[.!?]+/).filter(s => s.trim())
                        .length) || 1);
                    const asl = words / sentences;
                    parts.push({
                        label: 'Readability',
                        max: 5,
                        score: asl <= 20 ? 5 : (asl <= 28 ? 3 : 1)
                    });

                    this.breakdown = parts;
                    this.score = Math.min(100, Math.max(0, parts.reduce((a, b) => a + b.score, 0)));
                },

                get scoreCategory() {
                    return this.score >= 80 ? 'Good' : (this.score >= 60 ? 'Needs Improvement' : 'Poor');
                },
                get scoreStroke() {
                    return this.score >= 80 ? '#10b981' : (this.score >= 60 ? '#f59e0b' : '#f43f5e');
                },
                get scoreBadgeClass() {
                    return this.score >= 80 ? 'bg-emerald-50 text-emerald-700' :
                        (this.score >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700');
                },
            };
        }
    </script>
@endpush
