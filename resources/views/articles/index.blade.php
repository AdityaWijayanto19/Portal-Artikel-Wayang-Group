@extends('layouts.app')

@section('title', 'Artikel · ' . $company->name)
@section('subtitle', 'Susun, nilai SEO, dan publikasikan artikel ke situs WordPress ' . $company->name . '.')

@section('header_actions')
    <div class="flex items-center gap-2">
        @hasrole('super_admin')
            <a href="{{ route('articles.index') }}"
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
                        <div class="space-y-1">
                            @forelse ($article->sitePublications as $pub)
                                @php
                                    [$pLabel, $pColor] = $statusMap[$pub->status] ?? ['—', 'slate'];
                                @endphp
                                <div class="flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $pColor }}-500 shrink-0"></span>
                                    <span
                                        class="text-[11px] text-slate-600 truncate max-w-[140px]">{{ $pub->wpSite->site_name ?? 'Situs #' . $pub->wp_site_id }}</span>
                                    <span
                                        class="text-[9px] text-{{ $pColor }}-600 font-semibold uppercase">{{ $pLabel }}</span>
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
