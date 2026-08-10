@props(['dot' => 'bg-brand', 'meta' => ''])

<div class="flex items-start gap-3 pb-3 border-b border-slate-100 last:border-b-0 last:pb-0">
    <div class="w-2 h-2 rounded-full {{ $dot }} mt-1.5 shrink-0"></div>
    <div class="min-w-0">
        <p class="text-slate-800 font-medium leading-snug truncate">{{ $slot }}</p>
        <span class="text-[10px] text-slate-400 block mt-0.5">{{ $meta }}</span>
    </div>
</div>
