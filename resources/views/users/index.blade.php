@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola pengguna dan akun Author yang terhubung ke situs WordPress Perusahaan')

@section('header_actions')
    <a href="{{ route('users.create') }}"
        class="inline-flex items-center gap-2 bg-[#C59B27] hover:bg-[#b08820] text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Pengguna</span>
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

        @if (session('error'))
            <div
                class="bg-rose-50 border border-rose-200 text-rose-800 text-xs px-4 py-3 rounded-xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <x-table :headers="[
            'Pengguna',
            'Username WP',
            'Perusahaan',
            ['label' => 'Role', 'align' => 'center'],
            ['label' => 'Aksi', 'align' => 'right'],
        ]" :search-action="route('users.index')" search-placeholder="Cari nama, email, atau username..."
            :pagination="$users->hasPages() ? $users->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if (request()->filled('search'))
                    <a href="{{ route('users.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap ml-1">Reset</a>
                @endif
            </x-slot:filters>

            {{-- Table Body Rows --}}
            @forelse($users as $user)
                <tr class="hover:bg-slate-50/60 transition">
                    <!-- Pengguna Avatar & Detail -->
                    <td class="px-6 py-4 font-medium text-slate-900 flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl border border-slate-200/60 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=C59B27&background=2A2A2A"
                                alt="{{ $user->name }}" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <span class="font-bold block text-slate-900 text-sm mb-0.5">{{ $user->name }}</span>
                            <span class="text-[10px] text-slate-400">{{ $user->email }}</span>
                        </div>
                    </td>

                    <td class="px-6 py-4 font-mono text-slate-600 font-semibold">
                        <span class="bg-slate-100 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60">
                            {{ $user->username }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-medium text-slate-800">
                        {{ $user->company->name ?? 'Wayang Group' }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        @php
                            $roleName = $user->roles->first()?->name ?? 'author';
                            $badgeClasses = match ($roleName) {
                                'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200/60',
                                'admin' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                                default => 'bg-blue-50 text-blue-700 border-blue-200/60',
                            };
                        @endphp
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold border {{ $badgeClasses }}">
                            {{ strtoupper(str_replace('_', ' ', $roleName)) }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <a href="{{ route('users.edit', $user) }}"
                                class="p-2 bg-amber-50 hover:bg-amber-100 text-[#C59B27] rounded-lg transition"
                                title="Edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            @if ($user->id !== auth()->id())
                                <x-delete-modal action="{{ route('users.destroy', $user) }}" title="Hapus Pengguna?">
                                    <x-slot:message>
                                        "<span class="font-semibold text-slate-700">{{ $user->name }}</span>" Data
                                        Pengguna
                                        ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
                                    </x-slot:message>
                                </x-delete-modal>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                        Tidak ada data pengguna ditemukan.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
