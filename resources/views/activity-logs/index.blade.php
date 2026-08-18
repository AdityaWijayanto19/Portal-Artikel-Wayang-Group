@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('subtitle', 'Jejak aktivitas pengguna di seluruh modul (khusus Super Admin & Admin).')

@section('content')
    @php
        $headers = [
            'Waktu',
            'Pengguna',
            'Perusahaan',
            ['label' => 'Aksi', 'align' => 'center'],
            'Keterangan',
        ];

        if ($canViewSensitiveDetails) {
            $headers[] = ['label' => 'IP', 'align' => 'right'];
        }
    @endphp

    <div class="space-y-6">

        <x-table :headers="$headers" :pagination="$logs->hasPages() ? $logs->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if ($scopeCompanyId === null && $companies->isNotEmpty())
                    <x-filter-select name="company_id" :options="$companies->pluck('name', 'id')" all-label="Semua Perusahaan" />
                @endif

                @if ($users->isNotEmpty())
                    <x-filter-select name="user_id" :options="$users->pluck('name', 'id')" all-label="Semua Pengguna" />
                @endif

                <x-filter-select name="action" :options="$actions" all-label="Semua Aksi" />

                <x-filter-date-range />

                @if (request()->hasAny(['company_id', 'user_id', 'action', 'date_from', 'date_to']))
                    <a href="{{ route('activity-logs.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap">Reset</a>
                @endif
            </x-slot:filters>

            @forelse($logs as $log)
                @php
                    $roleName = $log->user?->roles->first()?->name;
                    $roleBadgeClasses = match ($roleName) {
                        'super_admin' => 'bg-purple-50 text-purple-700 border-purple-200/60',
                        'admin' => 'bg-amber-50 text-amber-700 border-amber-200/60',
                        'author' => 'bg-blue-50 text-blue-700 border-blue-200/60',
                        default => 'bg-slate-100 text-slate-600 border-slate-200/60',
                    };
                @endphp
                <tr class="hover:bg-slate-50/60 transition align-top">
                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                        {{ $log->created_at?->format('d M Y H:i') ?? '-' }}
                    </td>
                    <td class=" text-center px-6 py-4">
                        <span class="font-bold block text-slate-900 text-sm mb-0.5">{{ $log->user?->name ?? 'Sistem' }}</span>
                        @if ($roleName)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border {{ $roleBadgeClasses }}">
                                {{ strtoupper(str_replace('_', ' ', $roleName)) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $log->company?->name ?? 'Wayang Group (Global)' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/60">
                            {{ \App\Support\ActivityAction::label($log->action) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-700 max-w-md">
                        {{ $log->description }}
                    </td>
                    @if ($canViewSensitiveDetails)
                        <td class="px-6 py-4 text-right font-mono text-slate-500">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canViewSensitiveDetails ? 6 : 5 }}" class="px-6 py-12 text-center text-slate-400">
                        Tidak ada data log aktivitas ditemukan.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
