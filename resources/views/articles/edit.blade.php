@extends('layouts.app')

@section('title', 'Edit Artikel · ' . $article->title)
@section('subtitle', 'Perbarui artikel dan evaluasi SEO sebelum publikasi ulang.')
@section('back_url', route('articles.index'))

@section('header_actions')
    <div class="flex items-center gap-2">
        @if ($article->sitePublications->where('status', 'published')->isNotEmpty())
            <span
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-lg">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ $article->sitePublications->where('status', 'published')->count() }} situs terpublikasi
            </span>
        @endif
        <form action="{{ route('articles.destroy', $article) }}" method="POST"
            onsubmit="return confirm('Hapus artikel \"{{ $article->title }}\" beserta post-nya di WordPress? Tindakan ini permanen.');">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="inline-flex items-center gap-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold px-3 py-2 rounded-lg transition border border-rose-200">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus Artikel</span>
            </button>
        </form>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs px-4 py-3 rounded-xl shadow-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('articles.update', $article) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $article->company_id }}">
            @include('articles.partials._form')
        </form>
    </div>
@endsection
