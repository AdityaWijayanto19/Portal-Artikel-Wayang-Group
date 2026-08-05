@extends('layouts.app')

@section('title', 'SEO Article Editor')
@section('subtitle', 'Pilih perusahaan untuk mengelola artikel dan publikasi WordPress-nya.')

@section('content')
    <div class="space-y-6">

        <div class="bg-amber-50/60 border border-amber-200/70 rounded-xl px-4 py-3 flex items-start gap-3">
            <svg class="w-4 h-4 text-[#C59B27] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-xs text-amber-800 leading-relaxed">
                Sebagai <strong>Super Admin</strong>, pilih perusahaan terlebih dahulu. Seluruh artikel, kategori, tag, dan
                situs WordPress yang tampil setelah ini <strong>terisolasi</strong> hanya untuk perusahaan tersebut.
            </p>
        </div>

        @if ($companies->isEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center text-slate-400 text-xs">
                Belum ada perusahaan terdaftar.
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($companies as $company)
                    <a href="{{ route('articles.company', $company) }}"
                        class="group bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-[#C59B27]/60 transition-all duration-200 p-5 flex flex-col gap-4">

                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                                @if ($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}"
                                        class="w-full h-full object-cover">
                                @else
                                    <span
                                        class="text-sm font-bold text-[#C59B27]">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-slate-900 truncate group-hover:text-[#C59B27] transition">
                                    {{ $company->name }}</h3>
                                <p class="text-[11px] text-slate-400 font-mono truncate">{{ $company->slug }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-100">
                            <div class="text-center">
                                <span class="block text-sm font-bold text-slate-800">{{ $company->articles_count }}</span>
                                <span class="block text-[10px] text-slate-400 uppercase tracking-wide">Artikel</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-sm font-bold text-slate-800">{{ $company->wp_sites_count }}</span>
                                <span class="block text-[10px] text-slate-400 uppercase tracking-wide">Situs</span>
                            </div>
                            <div class="text-center">
                                <span class="block text-sm font-bold text-slate-800">{{ $company->users_count }}</span>
                                <span class="block text-[10px] text-slate-400 uppercase tracking-wide">Penulis</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end text-[11px] font-semibold text-[#C59B27] gap-1">
                            <span>Kelola Artikel</span>
                            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection
