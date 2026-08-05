@props(['type' => 'submit'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => 'w-full py-3 px-4 bg-[#1e1b18] hover:bg-[#2c2723] text-[#f4efe6] text-sm font-semibold rounded-xl border border-[#d4af37]/40 shadow-lg hover:shadow-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#d4af37] disabled:opacity-50 cursor-pointer']) }}>
    {{ $slot }}
</button>
