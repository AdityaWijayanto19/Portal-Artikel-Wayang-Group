@props(['name', 'options' => [], 'allLabel' => 'Semua', 'value' => null])

@php
    $allOptions = collect(['' => $allLabel])->merge($options);
@endphp

<div class="w-40 shrink-0">
    <x-select :name="$name" :options="$allOptions" :value="$value ?? request($name)" auto-submit />
</div>
