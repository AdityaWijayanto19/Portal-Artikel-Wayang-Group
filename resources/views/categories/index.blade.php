@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-serif font-bold text-brand-brown tracking-tight">Manajemen Kategori</h1>
            <p class="text-xs text-slate-500 mt-1">Kelola kelompok kategori artikel per perusahaan untuk mengatur konten publikasi.</p>
        </div>

        <div>
            <a href="{{ route('categories.create') }}" class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Kategori</span>
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <form action="{{ route('categories.index') }}" method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-80">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau slug..." class="w-full bg-slate-50/70 text-slate-800 text-xs border border-slate-200/80 rounded-xl pl-9 pr-3 py-2.5 focus:outline-none focus:border-[#C59B27] focus:bg-white transition">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                @if(request('search'))
                    <a href="{{ route('categories.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Slug</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl border border-slate-200/60 bg-slate-50 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-[#C59B27]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                    </div>
                                    <div>
                                        <span class="font-bold block text-slate-900">{{ $category->name }}</span>
                                        <span class="text-[10px] text-slate-400">{{ $category->created_at->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-600 font-semibold">
                                <span class="bg-slate-100 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60">{{ $category->slug }}</span>
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-800">
                                Aktif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('categories.edit', $category) }}" class="p-2 bg-amber-50 hover:bg-amber-100 text-[#C59B27] rounded-lg transition" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>

                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus kategori {{ $category->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-lg transition" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                Tidak ada data kategori ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
