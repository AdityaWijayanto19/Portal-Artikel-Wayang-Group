@props([
    'name' => 'content',
    'label' => null,
    'value' => null,
    'required' => false,
    'placeholder' => 'Tulis isi artikel di sini...',
])

{{--
    WYSIWYG editor berbasis Trix (Basecamp) via CDN — tanpa build step, selaras
    dengan pola Alpine CDN proyek ini. Output HTML bersih (heading, link, list)
    tersimpan di <input type="hidden"> sehatnya sehingga langsung terbaca oleh
    SeoAnalyzerService (deteksi internal/external link & keyword di konten).
--}}
@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/trix@2.1.15/dist/trix.css">
    @endpush
    @push('scripts')
        <script type="text/javascript" src="https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js"></script>
        <style>
            trix-editor {
                min-height: 420px;
                border: 1px solid #cbd5e1;
                border-radius: 0 0 0.75rem 0.75rem;
                background: #fff;
                font-size: 0.875rem;
                color: #1e293b;
            }
            trix-editor:focus {
                outline: none;
                border-color: var(--color-primary);
                box-shadow: 0 0 0 1px var(--color-primary);
            }
            trix-toolbar .trix-button-group {
                border-color: #e2e8f0;
            }
            trix-toolbar {
                border: 1px solid #cbd5e1;
                border-bottom: none;
                border-radius: 0.75rem 0.75rem 0 0;
                background: #f8fafc;
                padding: 0.4rem 0.5rem;
            }
            trix-editor h1 { font-size: 1.5rem; font-weight: 700; }
            trix-editor a { color: var(--color-primary); text-decoration: underline; }
        </style>
    @endpush
@endonce

<div class="space-y-1.5">
    @if ($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }} @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <input id="{{ $name }}_input" type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}">
    <trix-editor input="{{ $name }}_input" placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $errors->has($name) ? 'trix-error' : '']) }}
        x-ref="editor"
        @trix-change="$dispatch('content-updated', $refs.editor.editor.getDocument().toString() ? $el.value : $el.value)">
    </trix-editor>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>
