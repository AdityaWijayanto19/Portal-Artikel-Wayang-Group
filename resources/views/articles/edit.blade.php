@extends('layouts.app')

@section('title', 'Edit Artikel · ' . $article->title)
@section('subtitle', 'Perbarui artikel dan evaluasi SEO sebelum publikasi ulang.')
@section('back_url', route('articles.index'))

@section('header_actions')
    <div class="flex items-center gap-2">
        @if ($article->sitePublications->where('status', 'published')->isNotEmpty())
            <span
                class="items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3 py-2 rounded-lg">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ $article->sitePublications->where('status', 'published')->count() }} situs terpublikasi
            </span>
        @endif
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

        {{-- Info jadwal aktif — di LUAR form utama agar bisa memuat form aksi sendiri
             (nested form tidak sah di HTML). Ubah waktunya lewat tombol "Jadwalkan"
             pada form di bawah. --}}
        @if ($article->status === 'scheduled' && $article->scheduled_at)
            <div
                class="bg-violet-50 border border-violet-200 rounded-xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-violet-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-violet-800">Artikel terjadwal publish</p>
                        <p class="text-xs text-violet-600">
                            Dikirim otomatis ke {{ $article->sitePublications->count() }} situs target pada
                            <strong>{{ $article->scheduled_at->format('d M Y, H:i') }} WIB</strong>.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('articles.publish', $article) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">Publish
                            segera</button>
                    </form>
                    <form action="{{ route('articles.schedule.cancel', $article) }}" method="POST"
                        onsubmit="return confirm('Batalkan jadwal publish? Artikel akan kembali ke draft.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-600 text-xs font-semibold rounded-lg border border-slate-200 transition">Batalkan
                            jadwalkan</button>
                    </form>
                </div>
            </div>
        @endif

        <form action="{{ route('articles.update', $article) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $article->company_id }}">
            @include('articles.partials._form')
        </form>

        {{-- Modal jadwalkan di luar form utama: tombol di dalamnya tak bisa ikut
             submit form, dan selalu tampil di depan (di luar kolom sticky). --}}
        @include('articles.partials._schedule-modal')
    </div>
@endsection
