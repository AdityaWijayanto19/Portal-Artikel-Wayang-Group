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

        <x-table :headers="[
            'Perusahaan',
            'Slug',
            'Pengguna',
            'Artikel',
            'Situs WP',
            'Status',
            ['label' => 'Aksi', 'align' => 'right'],
        ]" :search-action="route('companies.index')" search-placeholder="Cari nama, email, atau username..."
            :pagination="$companies->hasPages() ? $companies->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if (request()->filled('search'))
                    <a href="{{ route('companies.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap ml-1">Reset</a>
                @endif
            </x-slot:filters>

            {{-- Table Body Rows --}}
            @forelse($companies as $company)
                <tr class="hover:bg-slate-50/80 transition">
                    <td class="px-5 py-3.5 font-medium text-slate-900 flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-lg border border-slate-200 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                            @if ($company->logo_path)
                                <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}"
                                    class="w-full h-full object-cover">
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
                        <x-delete-modal action="{{ route('companies.destroy', $company) }}" title="Hapus Perusahaan?">
                            <x-slot:message>
                                "<span class="font-semibold text-slate-700">{{ $company->name }}</span>" Data Perusahaan
                                ini akan
                                dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                            </x-slot:message>
                        </x-delete-modal>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-5 py-8 text-center text-slate-400">
                        Tidak ada data perusahaan ditemukan.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
