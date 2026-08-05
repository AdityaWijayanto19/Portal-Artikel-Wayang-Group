@props([
    'name' => 'content',
    'label' => null,
    'value' => null,
    'required' => false,
    'placeholder' => 'Tulis isi artikel di sini. Gunakan Heading 2 untuk sub-judul...',
])

{{--
    Smart Rich Text Editor berbasis TinyMCE 6 (editor resmi WordPress) via CDN — tanpa
    build step, selaras dengan pola Alpine/Trix CDN proyek ini.

    Tujuan utama (sesuai spesifikasi refactor):
    1. SMART PASTE — saat menempel dari Microsoft Word / Google Docs, seluruh style kotor
       (mso-*, font, warna, span inline, komentar <!--[if ...]-->) dibersihkan otomatis via
       paste_preprocess + paste_postprocess. Struktur (heading, bullet, numbering, paragraf,
       line break) TETAP dipertahankan.
    2. WORDPRESS RESULT PARITY — valid_elements di-whitelist ke tag semantik yang aman untuk
       WordPress REST API (p, h2, h3, ul/ol/li, a, strong/em, blockquote, img). Judul artikel
       adalah <h1> (di field terpisah), jadi editor default membentuk <p> dan sub-judul <h2>/<h3>.

    Output HTML bersih disimpan ke <textarea> tersembunyi (name="{{ $name }}") dan disinkronkan
    ke Alpine lewat event `content-updated` agar SEO analyzer realtime tetap jalan.
--}}
@once
    @push('scripts')
        {{-- TinyMCE 6 community (no-API-key) via jsDelivr --}}
        <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
        <style>
            /* Sembunyikan textarea asli; TinyMCE me-render iframe di atasnya. */
            .rich-editor-textarea { display: none; }
            .tox-tinymce {
                border: 1px solid #cbd5e1 !important;
                border-radius: 0.75rem !important;
            }
            .tox .tox-toolbar__primary { background: #f8fafc !important; }
        </style>
    @endpush
@endonce

<div class="space-y-1.5"
    x-data="richEditor('{{ $name }}')"
    x-init="init()"
>
    @if ($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }} @if ($required) <span class="text-rose-500">*</span> @endif
        </label>
    @endif

    <textarea
        id="{{ $name }}_editor"
        name="{{ $name }}"
        class="rich-editor-textarea"
        placeholder="{{ $placeholder }}"
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
        <script>
            function richEditor(name) {
                return {
                    editorId: name + '_editor',
                    init() {
                        const self = this;

                        tinymce.init({
                            selector: '#' + this.editorId,
                            height: 480,
                            menubar: false,
                            branding: false,
                            promotion: false,
                            statusbar: true,
                            plugins: 'lists link autolink wordcount table code',
                            toolbar: 'blocks | bold italic | bullist numlist | link blockquote | removeformat | code',
                            // Hanya izinkan format struktur SEO yang baik: Paragraf, H2, H3.
                            // (H1 dipakai untuk judul artikel di field terpisah.)
                            block_formats: 'Paragraf=p; Sub-judul (H2)=h2; Sub-sub-judul (H3)=h3',
                            content_style: 'body{font-family:ui-sans-serif,system-ui,sans-serif;font-size:14px;color:#1e293b;line-height:1.7} h2{font-size:1.35rem;font-weight:700;margin:1.2em 0 .4em} h3{font-size:1.15rem;font-weight:600;margin:1em 0 .3em} a{color:#C59B27;text-decoration:underline}',

                            // ==== WORDPRESS PARITY: whitelist tag semantik & buang atribut kotor ====
                            valid_elements: 'p,br,strong/b,em/i,u,h2,h3,ul,ol,li,a[href|title|target|rel],blockquote,img[src|alt|title|width|height],table,thead,tbody,tr,td,th',
                            valid_styles: {}, // buang SEMUA inline style (mso-*, font, color, dsb)
                            invalid_styles: '*',
                            forced_root_block: 'p',

                            // ==== SMART PASTE (Word / Google Docs) ====
                            paste_as_text: false,
                            paste_data_images: false,
                            paste_remove_styles_if_webkit: true,
                            paste_webkit_styles: 'none',
                            paste_merge_formats: true,
                            smart_paste: true,
                            // Bersihkan markup kotor Word/Docs sebelum masuk ke editor.
                            paste_preprocess(plugin, args) {
                                let html = args.content;
                                html = html
                                    .replace(/<!--[\s\S]*?-->/g, '')          // komentar <!--[if gte mso]-->
                                    .replace(/<o:p>[\s\S]*?<\/o:p>/gi, '')     // tag Office <o:p>
                                    .replace(/<\/?(span|font)[^>]*>/gi, '')    // span/font pembawa style
                                    .replace(/\sclass="?Mso[^"\s>]*"?/gi, '')  // class MsoNormal dll
                                    .replace(/\sstyle="[^"]*"/gi, '')          // semua inline style
                                    .replace(/\slang="[^"]*"/gi, '')
                                    .replace(/&nbsp;/gi, ' ');
                                args.content = html;
                            },
                            // Pastikan tak ada style tersisa setelah normalisasi TinyMCE.
                            paste_postprocess(plugin, args) {
                                args.node.querySelectorAll('[style]').forEach(el => el.removeAttribute('style'));
                                args.node.querySelectorAll('[class]').forEach(el => el.removeAttribute('class'));
                            },

                            setup(editor) {
                                const sync = () => {
                                    editor.save(); // tulis balik ke <textarea>
                                    const html = editor.getContent();
                                    self.$dispatch('content-updated', html);
                                };
                                editor.on('init', sync);
                                editor.on('change keyup input undo redo SetContent', sync);
                            },
                        });
                    },
                };
            }
        </script>
    @endpush
@endonce
