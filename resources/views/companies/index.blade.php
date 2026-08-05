@extends('layouts.app')

@section('title', 'Manajemen Perusahaan')
@section('subtitle', 'Kelola data tenant, organisasi, dan batasan ekosistem.')

@section('header_actions')
    <a href="{{ route('companies.create') }}"
        class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Perusahaan</span>
    </a>
@endsection

@section('content')
    <div class="space-y-6">

        <!-- Flash Notification -->
        @if (session('success'))
            <div
                class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Table Container -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <!-- Filter & Search Bar -->
            <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form action="{{ route('companies.index') }}" method="GET" class="flex items-center gap-2 w-full sm:w-80">
                    <div class="relative w-full">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Cari nama atau slug..."
                            class="w-full bg-slate-50 text-slate-800 text-xs border border-slate-200 rounded-lg pl-8 pr-3 py-2 focus:outline-none focus:border-[#C59B27]">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    @if (request('search'))
                        <a href="{{ route('companies.index') }}"
                            class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap">Reset</a>
                    @endif
                </form>
            </div>

            <!-- Table Data -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Perusahaan</th>
                            <th class="px-5 py-3">Slug</th>
                            <th class="px-5 py-3 text-center">Pengguna</th>
                            <th class="px-5 py-3 text-center">Artikel</th>
                            <th class="px-5 py-3 text-center">Situs WP</th>
                            <th class="px-5 py-3 text-center">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($companies as $company)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-5 py-3.5 font-medium text-slate-900 flex items-center gap-3">
                                    <div
                                        class="w-9 h-9 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                                        @if ($company->logo_path)
                                            <img src="{{ asset('storage/' . $company->logo_path) }}"
                                                alt="{{ $company->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span
                                                class="text-xs font-bold text-[#C59B27]">{{ strtoupper(substr($company->name, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="font-bold block text-slate-800">{{ $company->name }}</span>
                                        <span class="text-[10px] text-slate-400">Dibuat:
                                            {{ $company->created_at->format('d M Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-slate-500">{{ $company->slug }}</td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-800">
                                    {{ $company->users_count }}</td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-800">
                                    {{ $company->articles_count }}</td>
                                <td class="px-5 py-3.5 text-center font-semibold text-slate-800">
                                    {{ $company->wp_sites_count }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($company->is_active)
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Aktif</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Non-aktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right space-x-2">
                                    <a href="{{ route('companies.show', $company) }}"
                                        class="p-1.5 inline-flex bg-slate-100 hover:bg-slate-200 text-slate-700 rounded transition"
                                        title="Detail">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('companies.edit', $company) }}"
                                        class="p-1.5 inline-flex bg-amber-50 hover:bg-amber-100 text-[#C59B27] rounded transition"
                                        title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <div x-data="{ open: false }" class="inline-block">
                                        <button @click="open = true"
                                            class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded transition"
                                            title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>

                                        <!-- Delete Modal -->
                                        <div x-show="open" style="display: none;"
                                            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
                                            <div @click.away="open = false"
                                                class="bg-white rounded-xl max-w-sm w-full p-5 shadow-lg border border-slate-200 text-left">
                                                <h4 class="text-sm font-bold text-slate-900 mb-1">Hapus Perusahaan?</h4>
                                                <p class="text-xs text-slate-500 mb-4">Apakah kamu yakin ingin menghapus
                                                    <strong>{{ $company->name }}</strong>? Tindakan ini tidak dapat
                                                    dibatalkan.</p>
                                                <div class="flex justify-end gap-2">
                                                    <button @click="open = false" type="button"
                                                        class="px-3 py-1.5 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg">Batal</button>
                                                    <form action="{{ route('companies.destroy', $company) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="px-3 py-1.5 text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-lg">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                                    Tidak ada data perusahaan ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($companies->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $companies->links() }}
                </div>
            @endif

        </div>
    </div>
@endsection
