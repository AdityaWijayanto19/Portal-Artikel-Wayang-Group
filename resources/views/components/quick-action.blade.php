@props(['href', 'label'])

<a href="{{ $href }}"
    class="p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-medium text-center text-slate-800 flex flex-col items-center gap-1.5 transition">
    @isset($icon)
        {{ $icon }}
    @endisset
    <span>{{ $label }}</span>
</a>
