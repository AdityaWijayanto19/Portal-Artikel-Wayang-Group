@props([
    'name',
    'label' => null,
    'rows' => 4,
    'placeholder' => '',
    'required' => false,
    'value' => null,
])

<div class="space-y-1.5">
    @if($label)
        <label for="{{ $name }}" class="block text-xs font-semibold text-brand-muted uppercase tracking-wider">
            {{ $label }} @if($required) <span class="text-brand">*</span> @endif
        </label>
    @endif

    <textarea name="{{ $name }}"
              id="{{ $name }}"
              rows="{{ $rows }}"
              placeholder="{{ $placeholder }}"
              {{ $required ? 'required' : '' }}
              {{ $attributes->merge([
                  'class' => 'w-full bg-brand-surface border text-xs text-brand rounded-lg px-3 py-2.5 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition placeholder:text-brand-muted/50 resize-y ' . ($errors->has($name) ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-brand-stroke')
              ]) }}>{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="text-[11px] text-red-400 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>
