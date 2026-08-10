@props(['type' => 'submit'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'w-full py-3 px-4 bg-brand hover:bg-brand/90 text-brand-text text-sm font-semibold rounded-xl border border-brand/40 shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand disabled:opacity-50 cursor-pointer']) }}>
    {{ $slot }}
</button>
