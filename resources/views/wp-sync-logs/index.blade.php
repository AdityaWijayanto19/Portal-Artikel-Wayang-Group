@extends('layouts.app')
@section('title', 'Riwayat Sinkronisasi WP')
@section('subtitle', 'Riwayat pengiriman & sinkronisasi artikel ke situs WordPress target (khusus Super Admin & Admin).')

@section('content')
    <div class="space-y-6">

        <x-table :headers="[
            'Waktu',
            'Artikel',
            'Perusahaan',
            'Situs WP',
            ['label' => 'Status', 'align' => 'center'],
            'Keterangan',
        ]" :pagination="$logs->hasPages() ? $logs->appends(request()->query())->links() : null">

            <x-slot:filters>
                @if ($sites->isNotEmpty())
                    <x-filter-select name="wp_site_id" :options="$sites->pluck('site_name', 'id')" all-label="Semua Situs" />
                @endif

                <x-filter-select name="status" :options="['success' => 'Sukses', 'failed' => 'Gagal']" all-label="Semua Status" />

                <x-filter-date name="date_from" />
                <x-filter-date name="date_to" />

                @if (request()->hasAny(['wp_site_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('wp-sync-logs.index') }}"
                        class="text-xs text-slate-500 hover:text-slate-800 underline whitespace-nowrap">Reset</a>
                @endif
            </x-slot:filters>

            @forelse($logs as $log)
                <tr class="hover:bg-slate-50/60 transition align-top">
                    <td class="px-6 py-4 whitespace-nowrap text-slate-500">
                        {{ $log->synced_at?->format('d M Y H:i') ?? ($log->created_at?->format('d M Y H:i') ?? '-') }}
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <span class="font-bold block text-slate-900 text-sm mb-0.5 truncate">{{ $log->article?->title ?? '(artikel terhapus)' }}</span>
                        <span class="text-[10px] text-slate-400">oleh {{ $log->article?->author?->name ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $log->article?->company?->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($log->wpSite)
                            <a href="{{ $log->wpSite->site_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-brand hover:underline font-semibold">{{ $log->wpSite->site_name }}</a>
                            @if ($log->wp_post_id)
                                <span class="text-[10px] text-slate-400 block mt-0.5">Post #{{ $log->wp_post_id }}</span>
                            @endif
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if ($log->status === 'success')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">SUKSES</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">GAGAL</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-600 max-w-md">
                        <p class="line-clamp-2">{{ $log->response_message ?? '-' }}</p>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                        Tidak ada data sinkronisasi ditemukan.
                    </td>
                </tr>
            @endforelse
        </x-table>
    </div>
@endsection
