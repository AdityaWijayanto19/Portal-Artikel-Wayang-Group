@extends('layouts.app')
@section('title', 'Manajemen Pengguna')
@section('subtitle', 'Kelola pengguna dan akun Author yang terhubung ke situs WordPress Perusahaan')

@section('header_actions')
    <a href="{{ route('users.create') }}"
        class="inline-flex items-center gap-2 bg-brand hover:bg-brand/90 text-brand-text text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <span>Tambah Pengguna</span>
    </a>
@endsection

@section('content')
    @php
        use App\Helpers\ColorHelper;
    @endphp
    <div class="space-y-6">

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
                @php
                    $userCompany = $user->company;
                    $avatarText = ltrim(ColorHelper::normalizeHex($userCompany?->primary_color, ColorHelper::DEFAULT_PRIMARY), '#');
                    $avatarBg = ltrim(ColorHelper::normalizeHex($userCompany?->sidebar_color, ColorHelper::DEFAULT_SIDEBAR), '#');
                @endphp
                <tr class="hover:bg-slate-50/60 transition">
                    <!-- Pengguna Avatar & Detail -->
                    <td class="px-6 py-4 font-medium text-slate-900 flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl border border-slate-200/60 bg-slate-50 flex items-center justify-center shrink-0 overflow-hidden">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color={{ $avatarText }}&background={{ $avatarBg }}"
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
                                class="p-2 bg-amber-50 hover:bg-amber-100 text-brand rounded-lg transition"
                                title="Edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>

                            @if ($user->id !== auth()->id())
                                @php
                                    $articleCount = (int) ($user->articles_count ?? 0);
                                    $userCompanyIds = $user->companyIds();
                                    $recipients = ($candidateUsers ?? collect())
                                        ->filter(fn ($candidate) => $candidate->id !== $user->id)
                                        ->filter(fn ($candidate) => array_intersect($candidate->companyIds(), $userCompanyIds) !== [])
                                        ->values();
                                    // Ada artikel tapi tidak ada penerima satu perusahaan → penghapusan diblokir.
                                    $blocked = $articleCount > 0 && $recipients->isEmpty();
                                @endphp

                                <x-delete-modal action="{{ route('users.destroy', $user) }}" title="Hapus Pengguna?"
                                    :confirm-disabled="$blocked">
                                    <x-slot:message>
                                        "<span class="font-semibold text-slate-700">{{ $user->name }}</span>" Data
                                        Pengguna
                                        ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.

                                        @if ($articleCount > 0)
                                            <span class="block mt-2 text-slate-700">
                                                Pengguna ini memiliki
                                                <strong>{{ $articleCount }}</strong> artikel —
                                                {{ $blocked
                                                    ? 'artikel harus dipindahkan lebih dulu, namun tidak ada pengguna lain dalam perusahaan yang sama.'
                                                    : 'artikelnya akan dipindahkan ke pengguna lain dalam perusahaan yang sama sebelum pengguna dihapus.' }}
                                            </span>
                                        @endif
                                    </x-slot:message>

                                    @if ($articleCount > 0 && ! $blocked)
                                        <x-slot:extra>
                                            <label for="transfer_user_id_{{ $user->id }}"
                                                class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                                Pindahkan {{ $articleCount }} artikel ke
                                                <span class="text-rose-500">*</span>
                                            </label>
                                            <select name="transfer_user_id" id="transfer_user_id_{{ $user->id }}"
                                                required
                                                class="w-full text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 bg-white text-slate-800 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs cursor-pointer">
                                                <option value="">— Pilih penerima —</option>
                                                @foreach ($recipients as $recipient)
                                                    <option value="{{ $recipient->id }}">
                                                        {{ $recipient->name }}
                                                        ({{
                                                            strtoupper(str_replace(
                                                                '_',
                                                                ' ',
                                                                $recipient->roles->first()?->name ?? 'author',
                                                            ))
                                                        }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </x-slot:extra>
                                    @endif
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
