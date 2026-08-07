@extends('layouts.app')

@section('title', 'Artikel · ' . $company->name)
@section('subtitle', 'Susun, nilai SEO, dan publikasikan artikel ke situs WordPress ' . $company->name . '.')

@push('styles')
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('header_actions')
    <div class="flex items-center gap-2">
        @hasrole('super_admin')
            <a href="{{ route('articles.select') }}"
                class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-3 py-2 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <span>Ganti Perusahaan</span>
            </a>
        @endhasrole
        <a href="{{ route('articles.create') }}"
            class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Tulis Artikel</span>
        </a>
    </div>
@endsection

@section('content')
    <div class="space-y-6">

        @if (session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs px-4 py-3 rounded-xl shadow-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-table :headers="[
            'Artikel',
            'Skor SEO',
            'Publikasi SEO',
            'Publikasi Situs',
            'Status',
            ['label' => 'Aksi', 'align' => 'right'],
        ]" :search-action="route('articles.index')" search-placeholder="Cari judul atau slug..." :pagination="$articles->hasPages() ? $articles->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if (request()->filled('search'))
                    <a href="{{ route('articles.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap ml-1">Reset</a>
                @endif
            </x-slot:filters>

            @forelse($articles as $article)
                @php
                    $score = (int) ($article->seoMeta->seo_score ?? ($article->seo_score ?? 0));
                    $scoreColor = $score >= 80 ? 'emerald' : ($score >= 60 ? 'amber' : 'rose');
                    $statusMap = [
                        'draft' => ['Draft', 'slate'],
                        'queued' => ['Antrean', 'amber'],
                        'published' => ['Terbit', 'emerald'],
                        'failed' => ['Gagal', 'rose'],
                    ];
                    [$statusLabel, $statusColor] = $statusMap[$article->status] ?? ['—', 'slate'];
                @endphp
                <tr class="hover:bg-slate-50/80 transition align-top">
                    <td class="px-5 py-3.5">
                        <div class="flex items-start gap-3">
                            <div
                                class="w-10 h-10 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                                @if ($article->featured_image_path)
                                    <img src="{{ asset('storage/' . $article->featured_image_path) }}"
                                        alt="{{ $article->image_alt_text }}" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold block text-slate-800 truncate max-w-xs">{{ $article->title }}</span>
                                <span
                                    class="text-[10px] text-slate-400 font-mono truncate block">{{ $article->slug }}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach ($article->categories as $category)
                                        <span
                                            class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-100 text-slate-600">{{ $category->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full text-xs font-bold bg-{{ $scoreColor }}-50 text-{{ $scoreColor }}-700 border border-{{ $scoreColor }}-200/70">
                            {{ $score }}
                        </span>
                    </td>
                    <td class="px-5 py-3.5">
                        <div class="space-y-1.5">
                            @forelse ($article->sitePublications as $pub)
                                @php
                                    [$pLabel, $pColor] = $statusMap[$pub->status] ?? ['—', 'slate'];
                                    $published = $pub->status === 'published';
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $pColor }}-500 shrink-0"></span>
                                    <div class="min-w-0 flex-1">
                                        @if ($published && $pub->published_url)
                                            <a href="{{ $pub->published_url }}" target="_blank" rel="noopener"
                                                class="text-[11px] text-slate-700 hover:text-[#C59B27] truncate block max-w-[140px]"
                                                title="Buka di WordPress">{{ $pub->wpSite->site_name ?? 'Situs #' . $pub->wp_site_id }}</a>
                                        @else
                                            <span
                                                class="text-[11px] text-slate-600 truncate block max-w-[140px]">{{ $pub->wpSite->site_name ?? 'Situs #' . $pub->wp_site_id }}</span>
                                        @endif
                                        <span class="text-[9px] text-{{ $pColor }}-600 font-semibold uppercase">{{ $pLabel }}</span>
                                    </div>
                                    @if ($published && $pub->wpSite)
                                        <form action="{{ route('articles.sites.destroy', [$article, $pub->wpSite]) }}"
                                            method="POST" class="inline"
                                            onsubmit="return confirm('Hapus publikasi dari {{ $pub->wpSite->site_name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-rose-400 hover:text-rose-600 p-0.5 transition shrink-0"
                                                title="Unpublish dari situs ini">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <span class="text-[11px] text-slate-400">Belum ada situs target</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-5 py-3.5 text-center">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-200/60">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-5 py-3.5 text-right whitespace-nowrap space-x-1">
                        <a href="{{ route('articles.edit', $article) }}"
                            class="p-1.5 inline-flex bg-amber-50 hover:bg-amber-100 text-[#C59B27] rounded transition"
                            title="Edit">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </a>

                        @if ($score >= 80 && in_array($article->status, ['draft', 'failed']))
                            <form action="{{ route('articles.publish', $article) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="p-1.5 inline-flex bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded transition"
                                    title="Publish ke WordPress">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                </button>
                            </form>
                        @endif

                        @if ($article->status === 'failed')
                            <form action="{{ route('articles.retry', $article) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="p-1.5 inline-flex bg-slate-100 hover:bg-slate-200 text-slate-600 rounded transition"
                                    title="Retry publish">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </form>
                        @endif

                        {{-- Hapus Artikel + modal konfirmasi --}}
                        <div x-data="{ deleteOpen: false }" class="inline-block align-middle">
                            <button type="button" @click="deleteOpen = true"
                                class="p-1.5 inline-flex bg-rose-50 hover:bg-rose-100 text-rose-500 rounded transition"
                                title="Hapus Artikel">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>

                            <div x-show="deleteOpen" x-cloak x-transition.opacity
                                class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                <div class="absolute inset-0 bg-black/40" @click="deleteOpen = false"></div>
                                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6"
                                    @keydown.escape.window="deleteOpen = false">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-800">Hapus Artikel?</h3>
                                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                                "<span class="font-semibold text-slate-700">{{ $article->title }}</span>"
                                                beserta post-nya di seluruh situs WordPress target akan dihapus
                                                permanen. Tindakan ini tidak dapat dibatalkan.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-5">
                                        <button type="button" @click="deleteOpen = false"
                                            class="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                                            Batal
                                        </button>
                                        <form action="{{ route('articles.destroy', $article) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="px-3.5 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition">
                                                Ya, Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-10 text-center text-slate-400">
                        Belum ada artikel. Klik <strong>Tulis Artikel</strong> untuk memulai.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
