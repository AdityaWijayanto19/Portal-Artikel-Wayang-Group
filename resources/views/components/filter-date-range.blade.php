@props(['from' => 'date_from', 'to' => 'date_to'])

<div class="flex items-center gap-1.5 shrink-0" aria-label="Filter rentang waktu">
    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 select-none">
        Dari
    </span>
    <input id="{{ $from }}" type="date" name="{{ $from }}" value="{{ request($from) }}"
        onchange="this.form.submit()"
        class="text-xs bg-white text-slate-800 border border-slate-300 rounded-xl px-2 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs">

    <span class="text-slate-300 select-none" aria-hidden="true">&ndash;</span>

    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 select-none">
        Sampai
    </span>
    <input id="{{ $to }}" type="date" name="{{ $to }}" value="{{ request($to) }}"
        onchange="this.form.submit()"
        class="text-xs bg-white text-slate-800 border border-slate-300 rounded-xl px-2 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition shadow-xs">
</div>