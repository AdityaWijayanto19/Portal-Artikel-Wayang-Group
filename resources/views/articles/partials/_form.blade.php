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
    articleId: @js($isEdit ? $article->id : null),
    checkKeywordUrl: @js(route('articles.check-keyword')),
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
                class="w-full bg-white text-slate-900 text-xl font-bold border-0 border-b border-slate-200 rounded-none px-1 py-2 focus:outline-none focus:border-brand transition placeholder:text-slate-300 placeholder:font-normal">
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
                    <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Optimasi SEO &amp; Google
                        Preview</span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="scoreBadgeClass">
                        SEO: <span x-text="seoScore"></span>/100
                    </span>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="readabilityBadgeClass">
                        Readability: <span x-text="readabilityScore"></span>/100
                    </span>
                </span>
                <svg class="w-4 h-4 text-slate-400 transition-transform" :class="seoOpen ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="seoOpen" x-collapse class="px-5 pb-5 space-y-5 border-t border-slate-100 pt-5">

                {{-- Field SEO --}}
                <div class="space-y-1.5">
                    <x-input name="yoast_focuskw" label="Focus Keyphrase" placeholder="mis. jasa logistik jakarta"
                        x-model="focuskw" @input.debounce.500ms="checkKeywordDuplicate(); recompute()" />
                    {{-- Warning duplikat keyword --}}
                    <div x-show="keywordDuplicate" x-cloak
                        class="flex items-center gap-1.5 text-[11px] text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Keyword ini sudah dipakai artikel lain. Pertimbangkan gunakan keyword unik.</span>
                    </div>
                    <div x-show="checkingKeyword" x-cloak
                        class="flex items-center gap-1.5 text-[11px] text-slate-400">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memeriksa duplikat...</span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="yoast_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">SEO
                        Title</label>
                    <input type="text" name="yoast_title" id="yoast_title" x-model="seoTitle" @input="recompute()"
                        placeholder="Judul untuk mesin pencari (50-60 karakter ideal)"
                        class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition">
                    <p class="text-[10px] text-slate-400">Kosongkan untuk memakai Judul Artikel. Panjang: <span
                            x-text="(seoTitle || articleTitle).length"></span> karakter</p>
                </div>

                <div class="space-y-1.5">
                    <label for="slug_field" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Slug
                        (URL)</label>
                    <input type="text" id="slug_field" x-model="slug"
                        @input="slug = slugify(slug); slugEdited = true; recompute()" placeholder="auto-dari-judul"
                        class="w-full bg-white text-slate-800 text-xs font-mono border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition">
                    <input type="hidden" name="slug" :value="slug">
                </div>

                <div class="space-y-1.5">
                    <label for="yoast_metadesc"
                        class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Meta Description</label>
                    <textarea name="yoast_metadesc" id="yoast_metadesc" rows="3" x-model="metadesc" @input="recompute()"
                        placeholder="Ringkasan menarik 120-156 karakter yang muncul di hasil pencarian Google..."
                        class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition resize-y"></textarea>
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

                {{-- ===== Analysis Panel: Tab + Accordion ===== --}}
                <div class="space-y-0">

                    {{-- Floating Tab Headers --}}
                    <div class="flex gap-1">
                        {{-- Tab SEO --}}
                        <button type="button" @click="activeTab = 'seo'"
                            :class="activeTab === 'seo'
                                ? 'border-slate-200 text-slate-800 bg-white border-t border-l border-r rounded-t-lg shadow-sm z-10'
                                : 'border-transparent text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-t-lg'"
                            class="px-4 py-2.5 text-xs font-bold transition flex items-center gap-2 relative">
                            {{-- SVG Face Icon --}}
                            <template x-if="seoScore >= 80">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 14.5c0 0 1 2 3 2s3-2 3-2v1c0 0-1 2-3 2s-3-2-3-2v-1z"/>
                                </svg>
                            </template>
                            <template x-if="seoScore >= 50 && seoScore < 80">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 14.5h6v1.5H9z"/>
                                </svg>
                            </template>
                            <template x-if="seoScore < 50">
                                <svg class="w-4 h-4 text-rose-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 16.5c0 0 1-2 3-2s3 2 3 2v-1c0 0-1-2-3-2s-3 2-3 2v1z"/>
                                </svg>
                            </template>
                            <span>SEO</span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="scoreBadgeClass"
                                x-text="seoScoreCategory"></span>
                        </button>

                        {{-- Tab Readability --}}
                        <button type="button" @click="activeTab = 'readability'"
                            :class="activeTab === 'readability'
                                ? 'border-slate-200 text-slate-800 bg-white border-t border-l border-r rounded-t-lg shadow-sm z-10'
                                : 'border-transparent text-slate-500 bg-slate-100 hover:bg-slate-200 rounded-t-lg'"
                            class="px-4 py-2.5 text-xs font-bold transition flex items-center gap-2 relative">
                            {{-- SVG Face Icon --}}
                            <template x-if="readabilityScore >= 80">
                                <svg class="w-4 h-4 text-emerald-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 14.5c0 0 1 2 3 2s3-2 3-2v1c0 0-1 2-3 2s-3-2-3-2v-1z"/>
                                </svg>
                            </template>
                            <template x-if="readabilityScore >= 50 && readabilityScore < 80">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 14.5h6v1.5H9z"/>
                                </svg>
                            </template>
                            <template x-if="readabilityScore < 50">
                                <svg class="w-4 h-4 text-rose-500 shrink-0" viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM8.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM15.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM9 16.5c0 0 1-2 3-2s3 2 3 2v-1c0 0-1-2-3-2s-3 2-3 2v1z"/>
                                </svg>
                            </template>
                            <span>Readability</span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="readabilityBadgeClass"
                                x-text="readabilityScoreCategory"></span>
                        </button>
                    </div>

                    {{-- Main Card --}}
                    <div class="border border-slate-200 rounded-b-lg rounded-tr-lg bg-white shadow-sm overflow-hidden">

                        {{-- Card Header: Active Tab Status + Chevron --}}
                        <button type="button" @click="analysisOpen = !analysisOpen"
                            class="w-full flex items-center justify-between px-4 py-3 border-b border-slate-100 hover:bg-slate-50 transition cursor-pointer text-left">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <span x-show="activeTab === 'seo'">
                                    SEO <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="scoreBadgeClass" x-text="seoScoreCategory"></span>
                                </span>
                                <span x-show="activeTab === 'readability'">
                                    Readability <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" :class="readabilityBadgeClass" x-text="readabilityScoreCategory"></span>
                                </span>
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform"
                                :class="analysisOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Card Body (Accordion) --}}
                        <div x-show="analysisOpen" x-collapse class="p-4 space-y-4">

                            {{-- SEO Content --}}
                            <div x-show="activeTab === 'seo'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <div class="flex items-center gap-4">
                                    <div class="relative w-24 h-24 shrink-0">
                                        <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0"
                                                stroke-width="9" />
                                            <circle cx="50" cy="50" r="42" fill="none"
                                                :stroke="scoreStroke" stroke-width="9" stroke-linecap="round"
                                                :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * seoScore / 100)"
                                                style="transition: stroke-dashoffset .5s ease, stroke .3s ease" />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-xl font-black text-slate-800" x-text="seoScore"></span>
                                            <span class="text-[9px] text-slate-400 uppercase">dari 100</span>
                                        </div>
                                    </div>
                                    <div class="text-[11px] rounded-lg px-3 py-2 flex-1"
                                        :class="seoScore >= 80 ? 'bg-emerald-50 text-emerald-700' : (seoScore >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')">
                                        <span x-show="seoScore >= 80">Skor SEO memenuhi syarat. Artikel siap dipublikasikan.</span>
                                        <span x-show="seoScore >= 50 && seoScore < 80">Skor SEO perlu perbaikan. Minimal <strong>80</strong> untuk publish.</span>
                                        <span x-show="seoScore < 50">Skor SEO rendah. Perbaiki indikator di bawah.</span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-1 max-h-56 overflow-y-auto pr-1">
                                    <template x-for="item in seoBreakdown" :key="item.label">
                                        <div class="flex items-center justify-between text-[11px] py-1.5 border-b border-slate-50">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <svg class="w-3 h-3 shrink-0"
                                                    :class="item.score >= item.max ? 'text-emerald-500' : (item.score > 0 ? 'text-amber-500' : 'text-slate-300')"
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

                            {{-- Readability Content --}}
                            <div x-show="activeTab === 'readability'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <div class="flex items-center gap-4">
                                    <div class="relative w-24 h-24 shrink-0">
                                        <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                                            <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0"
                                                stroke-width="9" />
                                            <circle cx="50" cy="50" r="42" fill="none"
                                                :stroke="readabilityStroke" stroke-width="9" stroke-linecap="round"
                                                :stroke-dasharray="264" :stroke-dashoffset="264 - (264 * readabilityScore / 100)"
                                                style="transition: stroke-dashoffset .5s ease, stroke .3s ease" />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <span class="text-xl font-black text-slate-800" x-text="readabilityScore"></span>
                                            <span class="text-[9px] text-slate-400 uppercase">dari 100</span>
                                        </div>
                                    </div>
                                    <div class="text-[11px] rounded-lg px-3 py-2 flex-1"
                                        :class="readabilityScore >= 80 ? 'bg-emerald-50 text-emerald-700' : (readabilityScore >= 50 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700')">
                                        <span x-show="readabilityScore >= 80">Skor Readability memenuhi syarat. Artikel mudah dibaca.</span>
                                        <span x-show="readabilityScore >= 50 && readabilityScore < 80">Skor Readability perlu perbaikan. Minimal <strong>80</strong> untuk publish.</span>
                                        <span x-show="readabilityScore < 50">Skor Readability rendah. Perbaiki indikator di bawah.</span>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-1 max-h-56 overflow-y-auto pr-1">
                                    <template x-for="item in readabilityBreakdown" :key="item.label">
                                        <div class="flex items-center justify-between text-[11px] py-1.5 border-b border-slate-50">
                                            <div class="flex items-center gap-1.5 min-w-0">
                                                <svg class="w-3 h-3 shrink-0"
                                                    :class="item.score >= item.max ? 'text-emerald-500' : (item.score > 0 ? 'text-amber-500' : 'text-slate-300')"
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
                        class="w-full bg-white text-slate-800 text-xs font-medium border border-slate-300 rounded-xl px-3.5 py-2.5 pr-10 appearance-none focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs cursor-pointer select-none">
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

            {{-- action diturunkan dari status; server tetap gatekeep skor SEO + Readability. --}}
            <input type="hidden" name="action" :value="status === 'published' ? 'publish' : 'draft'">

            <button type="submit" :disabled="status === 'published' && !canPublish"
                :class="(status === 'published' && !canPublish) ? 'opacity-50 cursor-not-allowed bg-slate-300' : (
                    status === 'published' ? 'bg-brand hover:bg-brand/90 text-brand-text' :
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
                <span x-show="status === 'published' && canPublish">Publish ke WordPress</span>
                <span x-show="status === 'published' && !canPublish">Publish (Skor belum 80)</span>
            </button>
            <p class="text-[10px] text-slate-400 text-center leading-relaxed">
                Publikasi diverifikasi ulang di server. SEO & Readability harus &ge; 80.
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
                    class="w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition">
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
                            class="w-4 h-4 rounded border-slate-300 text-brand accent-brand focus:ring-brand">
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
                        class="w-4 h-4 rounded border-slate-300 text-brand accent-brand focus:ring-brand">
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
         * Dual scoring: SEO (100) + Readability (100).
         */
        function siteLinker(initial) {
            const sitesForCategory = {};
            initial.siteCategories.forEach(s => {
                const siteId = String(s.id);
                s.categories.forEach(cid => {
                    (sitesForCategory[String(cid)] = sitesForCategory[String(cid)] || []).push(siteId);
                });
            });

            return {
                selectedCategories: [],
                selectedSites: [],
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

                autoSites() {
                    const set = new Set();
                    this.selectedCategories.forEach(cid => {
                        (sitesForCategory[cid] || []).forEach(sid => set.add(sid));
                    });
                    return set;
                },

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
         * Article Editor — Dual SEO + Readability scoring engine.
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
                articleId: initial.articleId || null,
                checkKeywordUrl: initial.checkKeywordUrl || '',

                // Dual scores
                seoScore: 0,
                readabilityScore: 0,
                seoBreakdown: [],
                readabilityBreakdown: [],

                // Keyword duplicate check
                keywordDuplicate: false,
                checkingKeyword: false,

                // UI state
                slugEdited: false,
                seoOpen: true,
                activeTab: 'seo',
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
                    if (!this.slugEdited) this.slug = this.slugify(this.articleTitle);
                    this.recompute();
                },

                onContentChange(e) {
                    this.content = e.detail ?? this.content;
                    this.recompute();
                },

                get effectiveTitle() {
                    return (this.seoTitle || this.articleTitle || '').trim();
                },

                get canPublish() {
                    return this.seoScore >= 80 && this.readabilityScore >= 80;
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

                extractFirstParagraph(content, maxWords = 100) {
                    const plain = content.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                    const words = plain.split(' ');
                    return words.slice(0, maxWords).join(' ');
                },

                extractHeadings(content) {
                    const headings = [];
                    const regex = /<h[23][^>]*>(.*?)<\/h[23]>/gi;
                    let match;
                    while ((match = regex.exec(content)) !== null) {
                        const clean = match[1].replace(/<[^>]+>/g, '').trim();
                        if (clean) headings.push(clean);
                    }
                    return headings;
                },

                splitSentences(content) {
                    const plain = content.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
                    return plain.split(/[.!?]+/).filter(s => s.trim()).map(s => s.trim());
                },

                getFirstWord(sentence) {
                    const cleaned = sentence.replace(/[^\w\s]/g, '').trim().toLowerCase();
                    return cleaned.split(/\s+/)[0] || '';
                },

                countSyllables(word) {
                    word = word.toLowerCase().trim();
                    if (word.length <= 3) return 1;
                    const matches = word.match(/[aiueo]/g);
                    return matches ? Math.max(1, matches.length) : 1;
                },

                // ===== Transition Words (ID + EN) =====
                hasTransitionWord(sentence) {
                    const lower = sentence.toLowerCase();
                    const words = [
                        // ── Addition ──
                        'selain itu', 'di samping itu', 'untuk itu',
                        'furthermore', 'moreover', 'additionally', 'in addition',
                        'also', 'besides', "what's more", 'as well', 'on top of that',

                        // ── Contrast ──
                        'namun', 'tetapi', 'akan tetapi', 'meskipun', 'walaupun',
                        'sebaliknya', 'alih-alih',
                        'however', 'on the other hand', 'on the other side',
                        'nevertheless', 'nonetheless', 'conversely',
                        'although', 'though', 'whereas', 'despite',
                        'in contrast', 'on the contrary',

                        // ── Cause / Effect ──
                        'karena', 'sebab', 'akibatnya', 'oleh karena itu',
                        'maka', 'sehingga', 'dengan demikian', 'sebagai hasilnya',
                        'consequently', 'thus', 'therefore', 'hence', 'accordingly',
                        'as a result',

                        // ── Example ──
                        'misalnya', 'contohnya', 'seperti', 'yaitu',
                        'for example', 'for instance', 'such as',
                        'in particular', 'specifically', 'namely',

                        // ── Sequence / Order ──
                        'pertama', 'kedua', 'ketiga', 'selanjutnya', 'kemudian', 'akhirnya',
                        'lalu', 'setelah itu', 'sebelumnya',
                        'hingga', 'sampai',
                        'firstly', 'secondly', 'thirdly',
                        'next', 'finally', 'lastly',
                        'afterwards', 'meanwhile', 'previously', 'subsequently',

                        // ── Summary / Conclusion ──
                        'singkatnya', 'intinya', 'pada dasarnya',
                        'dengan kata lain',
                        'basically', 'in other words',
                        'in conclusion', 'to sum up', 'in summary',
                        'in short', 'overall', 'briefly',

                        // ── Emphasis ──
                        'justru', 'bahkan', 'terutama', 'khususnya', 'utamanya',
                        'tentu', 'pasti', 'jelas', 'nyatanya', 'faktanya',
                        'instead',
                        'indeed', 'in fact', 'certainly',
                        'obviously', 'clearly', 'undoubtedly',

                        // ── Similarity ──
                        'similarly', 'likewise',

                        // ── Time ──
                        'jika', 'apabila', 'bila', 'seandainya', 'asalkan',
                        'di sisi lain',
                        'eventually', 'ultimately',
                    ];
                    return words.some(w => lower.includes(w));
                },

                isPassiveVoice(sentence) {
                    const lower = sentence.toLowerCase();
                    return /\bdi\w{2,}/u.test(lower) || /\bter\w{2,}/u.test(lower);
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

                    // ===== SEO SCORING (13 indikator, total = 100) =====
                    const seoParts = [];

                    // 1. Keyphrase in Title (12)
                    let kwInTitleScore = 0;
                    if (kw) {
                        const titleLower = title.toLowerCase();
                        if (titleLower.includes(kw)) {
                            kwInTitleScore = titleLower.startsWith(kw) ? 12 : 8;
                        }
                    }
                    seoParts.push({ label: 'Keyphrase in Title', max: 12, score: kwInTitleScore });

                    // 2. SEO Title Length (8)
                    const tl = title.length;
                    seoParts.push({
                        label: 'SEO Title Length', max: 8,
                        score: (tl >= 50 && tl <= 60) ? 8 : ((tl >= 35 && tl <= 75) ? 5 : (tl > 0 ? 3 : 0))
                    });

                    // 3. Keyphrase in Slug (8)
                    let slugScore = 0;
                    if (slug.length) {
                        slugScore += (kw && slug.toLowerCase().includes(kw)) ? 5 : 2;
                        slugScore += /^[a-z0-9\-]+$/.test(slug) ? 3 : 0;
                    }
                    seoParts.push({ label: 'Keyphrase in Slug', max: 8, score: Math.min(8, slugScore) });

                    // 4. Keyphrase in Introduction (10)
                    const firstPara = this.extractFirstParagraph(content);
                    seoParts.push({
                        label: 'Keyphrase in Introduction', max: 10,
                        score: (kw && firstPara.toLowerCase().includes(kw)) ? 10 : 0
                    });

                    // 5. Keyphrase Density (8)
                    const kwCount = kw ? (lc.split(kw).length - 1) : 0;
                    const density = (kwCount * 100) / Math.max(1, words);
                    seoParts.push({
                        label: 'Keyphrase Density', max: 8,
                        score: (density >= 0.5 && density <= 2.5) ? 8 : (density > 0 ? 4 : 0)
                    });

                    // 6. Keyphrase in Meta Description (10)
                    seoParts.push({
                        label: 'Keyphrase in Meta Description', max: 10,
                        score: (kw && meta.toLowerCase().includes(kw)) ? 10 : 0
                    });

                    // 7. Meta Description Length (7)
                    const ml = meta.length;
                    seoParts.push({
                        label: 'Meta Description Length', max: 7,
                        score: (ml >= 120 && ml <= 156) ? 7 : ((ml >= 90 && ml <= 180) ? 4 : (ml > 0 ? 2 : 0))
                    });

                    // 8. Keyphrase in Subheading (9)
                    const headings = this.extractHeadings(content);
                    const kwInHeading = kw && headings.some(h => h.toLowerCase().includes(kw));
                    seoParts.push({ label: 'Keyphrase in Subheading', max: 9, score: kwInHeading ? 9 : 0 });

                    // 9. Keyphrase in Image Alt (6)
                    seoParts.push({
                        label: 'Keyphrase in Image Alt', max: 6,
                        score: (kw && alt.toLowerCase().includes(kw)) ? 6 : (alt ? 3 : 0)
                    });

                    // 10. Internal Links (8)
                    const links = [...content.matchAll(/href=["']([^"']+)["']/gi)].map(m => m[1]);
                    seoParts.push({
                        label: 'Internal Links', max: 8,
                        score: links.some(u => u.startsWith('/') || !u.startsWith('http')) ? 8 : 0
                    });

                    // 11. Outbound Links (5)
                    seoParts.push({
                        label: 'Outbound Links', max: 5,
                        score: links.some(u => u.startsWith('http')) ? 5 : 0
                    });

                    // 12. Content Word Count (9)
                    seoParts.push({
                        label: 'Content Word Count', max: 9,
                        score: words >= 900 ? 9 : (words >= 300 ? 5 : (words > 0 ? 2 : 0))
                    });

                    // 13. Previously Used Keyphrase (8) — handled server-side, client shows placeholder
                    seoParts.push({ label: 'Previously Used Keyphrase', max: 8, score: 8 });

                    this.seoBreakdown = seoParts;
                    this.seoScore = Math.min(100, Math.max(0, seoParts.reduce((a, b) => a + b.score, 0)));

                    // ===== READABILITY SCORING (7 indikator, total = 100) =====
                    const readParts = [];
                    const sentences = this.splitSentences(content);

                    // 1. Paragraph Length (15)
                    const paragraphs = content.split(/<\/p>|<br\s*\/?>|\n{2,}/i).filter(p => p.trim());
                    const longParagraphs = paragraphs.filter(p => {
                        const wc = p.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(w => w).length;
                        return wc > 150;
                    }).length;
                    const paraRatio = (longParagraphs * 100) / Math.max(1, paragraphs.length);
                    readParts.push({
                        label: 'Paragraph Length', max: 15,
                        score: paraRatio <= 10 ? 15 : (paraRatio <= 25 ? 10 : (paraRatio <= 50 ? 5 : 0))
                    });

                    // 2. Sentence Length Ratio (18)
                    const longSentences = sentences.filter(s => s.split(/\s+/).length > 20).length;
                    const sentRatio = (longSentences * 100) / Math.max(1, sentences.length);
                    readParts.push({
                        label: 'Sentence Length', max: 18,
                        score: sentRatio <= 25 ? 18 : (sentRatio <= 40 ? 12 : (sentRatio <= 60 ? 6 : 0))
                    });

                    // 3. Subheading Distribution (15)
                    const headingCount = headings.length;
                    const avgWordsBetween = words / Math.max(1, headingCount);
                    readParts.push({
                        label: 'Subheading Distribution', max: 15,
                        score: words <= 300 ? 15 : (avgWordsBetween <= 300 ? 15 : (avgWordsBetween <= 450 ? 10 : (avgWordsBetween <= 600 ? 5 : 0)))
                    });

                    // 4. Transition Words (15)
                    const withTransition = sentences.filter(s => this.hasTransitionWord(s)).length;
                    const transRatio = (withTransition * 100) / Math.max(1, sentences.length);
                    readParts.push({
                        label: 'Transition Words', max: 15,
                        score: transRatio >= 30 ? 15 : (transRatio >= 20 ? 10 : (transRatio >= 10 ? 5 : 0))
                    });

                    // 5. Passive Voice (12)
                    const passiveCount = sentences.filter(s => this.isPassiveVoice(s)).length;
                    const passiveRatio = (passiveCount * 100) / Math.max(1, sentences.length);
                    readParts.push({
                        label: 'Passive Voice', max: 12,
                        score: passiveRatio <= 10 ? 12 : (passiveRatio <= 20 ? 8 : (passiveRatio <= 30 ? 4 : 0))
                    });

                    // 6. Consecutive Sentences (12)
                    let maxConsecutive = 1;
                    let currentConsecutive = 1;
                    for (let i = 1; i < sentences.length; i++) {
                        const prevWord = this.getFirstWord(sentences[i - 1]);
                        const currWord = this.getFirstWord(sentences[i]);
                        if (prevWord && currWord && prevWord === currWord) {
                            currentConsecutive++;
                            maxConsecutive = Math.max(maxConsecutive, currentConsecutive);
                        } else {
                            currentConsecutive = 1;
                        }
                    }
                    readParts.push({
                        label: 'Consecutive Sentences', max: 12,
                        score: maxConsecutive <= 2 ? 12 : (maxConsecutive <= 3 ? 8 : (maxConsecutive <= 4 ? 4 : 0))
                    });

                    // 7. Flesch Reading Ease (13)
                    const sentenceCount = Math.max(1, sentences.length);
                    let totalSyllables = 0;
                    sentences.forEach(s => {
                        s.split(/\s+/).forEach(w => { totalSyllables += this.countSyllables(w); });
                    });
                    let flesch = 206.835 - (1.015 * (words / sentenceCount)) - (84.6 * (totalSyllables / Math.max(1, words)));
                    flesch = Math.max(0, Math.min(100, flesch));
                    const fleschDiff = Math.abs(flesch - 60.0);
                    readParts.push({
                        label: 'Flesch Reading Ease', max: 13,
                        score: fleschDiff <= 10 ? 13 : (fleschDiff <= 20 ? 10 : (fleschDiff <= 30 ? 7 : 3))
                    });

                    this.readabilityBreakdown = readParts;
                    this.readabilityScore = Math.min(100, Math.max(0, readParts.reduce((a, b) => a + b.score, 0)));
                },

                async checkKeywordDuplicate() {
                    const kw = (this.focuskw || '').trim();
                    if (!kw || !this.checkKeywordUrl) {
                        this.keywordDuplicate = false;
                        return;
                    }

                    this.checkingKeyword = true;
                    try {
                        const url = new URL(this.checkKeywordUrl, window.location.origin);
                        url.searchParams.append('keyword', kw);
                        if (this.articleId) {
                            url.searchParams.append('article_id', this.articleId);
                        }

                        const res = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                            },
                        });

                        if (res.ok) {
                            const data = await res.json();
                            this.keywordDuplicate = data.duplicate || false;
                        }
                    } catch (e) {
                        console.warn('[KeywordCheck] gagal:', e);
                    } finally {
                        this.checkingKeyword = false;
                    }
                },

                // ===== Score Category Helpers =====
                get seoScoreCategory() {
                    return this.seoScore >= 80 ? 'Good' : (this.seoScore >= 60 ? 'Needs Improvement' : 'Poor');
                },
                get readabilityScoreCategory() {
                    return this.readabilityScore >= 80 ? 'Good' : (this.readabilityScore >= 60 ? 'Needs Improvement' : 'Poor');
                },
                get scoreStroke() {
                    return this.seoScore >= 80 ? '#10b981' : (this.seoScore >= 60 ? '#f59e0b' : '#f43f5e');
                },
                get readabilityStroke() {
                    return this.readabilityScore >= 80 ? '#10b981' : (this.readabilityScore >= 60 ? '#f59e0b' : '#f43f5e');
                },
                get scoreBadgeClass() {
                    return this.seoScore >= 80 ? 'bg-emerald-50 text-emerald-700' :
                        (this.seoScore >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700');
                },
                get readabilityBadgeClass() {
                    return this.readabilityScore >= 80 ? 'bg-emerald-50 text-emerald-700' :
                        (this.readabilityScore >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700');
                },
            };
        }
    </script>
@endpush
