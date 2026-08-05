@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('companies.index') }}" class="p-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold font-serif text-slate-900 tracking-wide">{{ $company->name }}</h1>
                <p class="text-xs text-slate-500 mt-0.5">Detail statistik & profil tenant.</p>
            </div>
        </div>

        <a href="{{ route('companies.edit', $company) }}" class="inline-flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 text-[#C59B27] text-xs font-semibold px-3 py-2 rounded-lg border border-amber-200/60 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Edit Perusahaan</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Overview Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-1">Pengguna Terdaftar</span>
            <p class="text-2xl font-bold text-slate-900">{{ $company->users_count }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-1">Total Artikel</span>
            <p class="text-2xl font-bold text-[#C59B27]">{{ $company->articles_count }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-1">Kategori Artikel</span>
            <p class="text-2xl font-bold text-slate-900">{{ $company->categories_count }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block mb-1">Situs WP Terhubung</span>
            <p class="text-2xl font-bold text-emerald-600">{{ $company->wp_sites_count }}</p>
        </div>
    </div>

    <!-- Main Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Info Card -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">Informasi Perusahaan</h3>

            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                    @if($company->logo_path)
                        <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-sm font-bold text-[#C59B27]">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
                    @endif
                </div>
                <div>
                    <p class="font-bold text-slate-900 text-sm">{{ $company->name }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ $company->slug }}</p>
                </div>
            </div>

            <div class="space-y-2 text-xs pt-2">
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Status:</span>
                    <span class="font-semibold text-slate-800">{{ $company->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-400">Dibuat Pada:</span>
                    <span class="font-semibold text-slate-800">{{ $company->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Latest Users List -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider pb-2 border-b border-slate-100">Pengguna Terbaru Tenant Ini</h3>

            <div class="divide-y divide-slate-100 text-xs">
                @forelse($latestUsers as $user)
                    <div class="py-2.5 flex items-center justify-between">
                        <div>
                            <p class="font-bold text-slate-800">{{ $user->name }}</p>
                            <p class="text-[10px] text-slate-400">{{ $user->email }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                            {{ $user->getRoleNames()->first() ?? 'User' }}
                        </span>
                    </div>
                @empty
                    <p class="text-slate-400 text-xs py-4 text-center">Belum ada pengguna terdaftar untuk tenant ini.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
