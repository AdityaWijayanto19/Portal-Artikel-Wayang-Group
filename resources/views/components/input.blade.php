@props([
    'name',
    'label' => null,
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'value' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }} @if($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <input type="{{ $type }}"
           name="{{ $name }}"
           id="{{ $name }}"
           value="{{ old($name, $value) }}"
           placeholder="{{ $placeholder }}"
           {{ $required ? 'required' : '' }}
           {{ $attributes->merge([
               'class' => 'w-full bg-white text-slate-800 text-xs border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:border-[#C59B27] focus:ring-1 focus:ring-[#C59B27] transition placeholder:text-slate-400 shadow-xs ' . ($errors->has($name) ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500' : '')
           ]) }}>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>
