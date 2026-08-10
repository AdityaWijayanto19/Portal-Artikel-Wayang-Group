@extends('layouts.app')
@section('title', 'Manajemen WP Site')
@section('subtitle', 'Kelola koneksi situs WordPress target yang menerima publikasi artikel.')

@section('header_actions')
    <a href="{{ route('wp-sites.create') }}"
        class="inline-flex items-center gap-2 bg-brand hover:bg-brand/90 text-brand-text text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah WP Site</span>
    </a>
@endsection

@section('content')
    <div class="space-y-6">

        <x-table :headers="['Situs', 'URL', 'Kategori', 'Username WP', ['label' => 'Aksi', 'align' => 'right']]" :search-action="route('wp-sites.index')" search-placeholder="Cari nama site atau url site..." :pagination="$wpSites->hasPages() ? $wpSites->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if (request()->filled('search'))
                    <a href="{{ route('users.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap ml-1">Reset</a>
                @endif
            </x-slot:filters>
            @forelse($wpSites as $wpSite)
                <tr class="hover:bg-slate-50/60 transition">
                    <td class="px-6 py-4 font-medium text-slate-900">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl border border-slate-200/60 bg-slate-50 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-brand opacity-80 shrink-0" xmlns="http://www.w3.org/2000/svg"
                                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    class="lucide lucide-globe-icon lucide-globe">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                                    <path d="M2 12h20" />
                                </svg>
                            </div>
                            <div>
                                <span class="font-bold block text-slate-900">{{ $wpSite->site_name }}</span>
                                <span class="text-[10px] text-slate-400">{{ $wpSite->created_at->format('d M Y') }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        <a href="{{ $wpSite->site_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-brand hover:underline font-semibold">{{ $wpSite->site_url }}</a>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse($wpSite->categories as $category)
                                <span
                                    class="inline-flex items-center gap-1 bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60 font-semibold">
                                    {{ $category->name }}
                                </span>
                            @empty
                                <span class="text-[11px] text-rose-500">Belum ada kategori</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-4 font-mono text-slate-600 font-semibold">
                        <span
                            class="bg-slate-100 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60">{{ $wpSite->wp_username }}</span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="{{ route('wp-sites.edit', $wpSite) }}"
                                class="p-2 bg-amber-50 hover:bg-amber-100 text-brand rounded-lg transition"
                                title="Edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            <x-delete-modal action="{{ route('wp-sites.destroy', $wpSite) }}"
                                title="Hapus Situs Wordpress?">
                                <x-slot:message>
                                    "<span class="font-semibold text-slate-700">{{ $wpSite->site_name }}</span>" Data
                                    Situs Wordpress ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                                </x-slot:message>
                            </x-delete-modal>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                        Tidak ada data WP Site ditemukan.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
