@extends('layouts.app')
@section('title', 'Visitor Analytics')
@section('subtitle', 'Ringkasan visitor (flag counter) dari setiap WP Site yang dilacak.')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($sites as $site)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col gap-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 truncate">{{ $site->site_name }}</h3>
                        <a href="{{ $site->site_url }}" target="_blank" rel="noopener noreferrer"
                            class="text-xs text-brand hover:underline font-semibold break-all">{{ $site->site_url }}</a>
                    </div>
                    <span
                        class="inline-flex items-center gap-1 shrink-0 bg-slate-100 text-slate-700 px-2.5 py-1 rounded-md text-[11px] border border-slate-200/60 font-semibold">
                        {{ $site->company?->name ?? '—' }}
                    </span>
                </div>

                <div class="flex-1 flex items-center justify-center p-1">
                    <img src="{{ $site->flag_counter_url }}" alt="Visitor Counter {{ $site->site_name }}"
                        class="max-w-full h-auto" loading="lazy">
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl border border-slate-200/80 shadow-sm p-12 text-center">
                <p class="text-sm font-semibold text-slate-500">Belum ada WP Site dengan Flag Counter URL.</p>
                <p class="text-xs text-slate-400 mt-1">Tambahkan Flag Counter URL pada WP Site untuk mulai melacak visitor.</p>
            </div>
        @endforelse
    </div>
@endsection
