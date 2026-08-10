@props(['label', 'value', 'tone' => 'brand'])

@php
    $tones = [
        'brand' => ['icon' => 'bg-amber-50 border-amber-200/60 text-brand', 'value' => 'text-slate-900'],
        'emerald' => ['icon' => 'bg-emerald-50 border-emerald-200/60 text-emerald-600', 'value' => 'text-emerald-600'],
        'amber' => ['icon' => 'bg-amber-50 border-amber-200/60 text-amber-600', 'value' => 'text-amber-600'],
        'rose' => ['icon' => 'bg-rose-50 border-rose-200/60 text-rose-600', 'value' => 'text-rose-600'],
    ];
@endphp

<div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
    <div class="space-y-1">
        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 block">{{ $label }}</span>
        <p class="text-2xl font-bold {{ $tones[$tone]['value'] }}">{{ $value }}</p>
        @isset($sub)
            <span class="text-[10px] text-slate-400 block">{{ $sub }}</span>
        @endisset
    </div>
    @isset($icon)
        <div class="p-2.5 rounded-lg border {{ $tones[$tone]['icon'] }} shrink-0">
            {{ $icon }}
        </div>
    @endisset
</div>
