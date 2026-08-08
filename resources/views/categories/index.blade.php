@extends('layouts.app')
@section('title', 'Manajemen Kategori')
@section('subtitle', 'Kelola kelompok kategori artikel per perusahaan untuk mengatur konten publikasi.')

@section('header_actions')
    <a href="{{ route('categories.create') }}"
        class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Kategori</span>
    </a>
@endsection

@section('content')
    <div class="space-y-6">

        <x-table :headers="['Kategori', 'Slug', 'Perusahaan', ['label' => 'Aksi', 'align' => 'right']]" :search-action="route('categories.index')" search-placeholder="Cari nama atau slug..." :pagination="$categories->hasPages() ? $categories->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if (request()->filled('search'))
                    <a href="{{ route('categories.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap ml-1">Reset</a>
                @endif
            </x-slot:filters>

            @forelse($categories as $category)
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-6 py-4 font-medium text-slate-900">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl border border-slate-200/60 bg-slate-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-[#C59B27]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold block text-slate-900">{{ $category->name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $category->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-slate-600 font-semibold">
                        <span
                            class="bg-slate-100 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60">{{ $category->slug }}</span>
                    </td>
                    <td class="px-6 py-4 font-sans text-slate-600 font-semibold">
                        {{ $category->company->name ?? 'N/A' }}

                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="{{ route('categories.edit', $category) }}"
                                class="p-2 bg-amber-50 hover:bg-amber-100 text-[#C59B27] rounded-lg transition"
                                title="Edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            <x-delete-modal action="{{ route('categories.destroy', $category) }}" title="Hapus Kategori?">

                                <x-slot:message>
                                    "<span class="font-semibold text-slate-700">{{ $category->name }}</span>" Kategori ini
                                    akan
                                    dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                                </x-slot:message>

                            </x-delete-modal>
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
        </x-table>
    </div>
@endsection
