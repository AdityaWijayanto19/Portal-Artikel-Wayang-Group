{{-- ===== 1. WORKFLOW ===== --}}
<section id="workflow" data-section class="scroll-mt-24 bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
    <header class="mb-5">
        <h2 class="flex items-baseline gap-2.5 font-sans text-lg font-bold text-slate-900">
            <span class="text-sm font-black text-brand">01</span> Workflow Penulisan
        </h2>
        <p class="mt-1 text-sm text-slate-500">Dari draf hingga artikel tampil di WordPress.</p>
    </header>

    <p class="text-sm text-slate-600 leading-relaxed mb-6">
        Setiap artikel mengikuti alur <strong class="text-slate-900">Draf → Antrean (Publish) → Published /
        Gagal</strong>. Tombol publikasi hanya aktif bila skor SEO <strong class="text-brand">≥ 80</strong>
        <strong>DAN</strong> skor Readability <strong class="text-brand">≥ 80</strong>, dan sistem tetap memverifikasi ulang
        skor di server sebelum benar-benar mengirim ke WordPress.
    </p>

    <ol class="space-y-0">
        @foreach ([
            'Pilih perusahaan / buka Manajemen Artikel' => 'Super admin memilih fokus perusahaan di menu; admin & author otomatis terhubung ke perusahaannya sendiri. Klik <strong>Manajemen Artikel</strong> di menu samping, lalu tombol <em>Tambah Artikel</em>.',
            'Isi draf artikel' => 'Lengkapi judul, isi, gambar unggulan, kategori, tag, dan situs WordPress target (detail di Bagian 2). Anda bisa <em>Simpan Draft</em> kapan pun dan melanjutkannya nanti.',
            'Review SEO & Google Preview' => 'Pantau skor SEO realtime di panel <em>Optimasi SEO & Google Preview</em>. Pastikan skor <strong>≥ 80</strong> dan preview di Google terlihat menarik sebelum dipublikasikan.',
            'Publish ke WordPress' => 'Pilih status <em>Published (kirim ke WordPress)</em>. Artikel masuk <strong>antrean (queued)</strong> dan dikirim satu per satu ke setiap situs target. Satu situs gagal tidak mengganggu situs lainnya.',
            'Pantau status & perbaiki bila gagal' => 'Status berubah menjadi <strong>Published</strong> bila sukses, atau <strong>Failed</strong> bila ada kendala. Gunakan tombol <em>Retry</em> untuk mengirim ulang situs yang gagal; cek kredensial WordPress bila kegagalan berulang.',
        ] as $stepTitle => $stepDesc)
            <li class="relative flex gap-4 pb-5 last:pb-0">
                <span class="relative z-10 mt-0.5 w-6 h-6 shrink-0 rounded-full bg-brand/10 border border-brand/30 text-brand text-[11px] font-bold flex items-center justify-center">{{ $loop->iteration }}</span>
                <div>
                    <p class="text-sm font-semibold text-slate-800">{{ $stepTitle }}</p>
                    <p class="text-[13px] text-slate-500 leading-relaxed mt-0.5">{!! $stepDesc !!}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

{{-- ===== 2. PANDUAN FORM ===== --}}
<section id="form" data-section class="scroll-mt-24 bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
    <header class="mb-5">
        <h2 class="flex items-baseline gap-2.5 font-sans text-lg font-bold text-slate-900">
            <span class="text-sm font-black text-brand">02</span> Panduan Form Penulisan Artikel
        </h2>
        <p class="mt-1 text-sm text-slate-500">Cara mengisi setiap bagian form sesuai aturan sistem.</p>
    </header>

    <div class="space-y-7">
        <div id="form-judul" data-section>
            <h3 class="text-[13px] font-bold uppercase tracking-wide text-brand mb-3">2.1 · Judul &amp; Isi Artikel</h3>
            <ul class="list-disc pl-5 space-y-2 text-sm text-slate-600 leading-relaxed">
                <li><strong class="text-slate-900">Judul</strong> wajib diisi, minimal <strong class="text-slate-900">10 karakter</strong> dan maksimal <strong class="text-slate-900">255 karakter</strong>. Menjadi tag <code class="text-brand bg-slate-100 px-1 py-0.5 rounded text-[12px]">&lt;h1&gt;</code> pada artikel WordPress. Idealnya 50–60 karakter agar skor SEO Title maksimal.</li>
                <li><strong class="text-slate-900">Isi Artikel</strong> minimal <strong class="text-slate-900">200 karakter</strong>. Target konten <strong class="text-brand">≥ 800 kata</strong> untuk skor penuh pada indikator Content Length.</li>
                <li>Gunakan <strong class="text-slate-900">editor kaya (TinyMCE)</strong>: <strong class="text-slate-900">H2</strong> untuk sub-judul utama, <strong class="text-slate-900">H3</strong> untuk sub-bagian, serta blockquote, list, dan tebal/miring.</li>
                <li>Menempel dari <strong class="text-slate-900">Word / Google Docs</strong> aman format dibersihkan otomatis. Periksa struktur heading (H2/H3) setelah menempel.</li>
                <li><strong class="text-slate-900">Slug (URL)</strong> otomatis dibuat dari judul (huruf kecil &amp; strip). Bisa diedit manual; hanya boleh huruf <code class="text-brand bg-slate-100 px-1 py-0.5 rounded text-[12px]">a-z</code>, angka, dan strip <code class="text-brand bg-slate-100 px-1 py-0.5 rounded text-[12px]">-</code>.</li>
            </ul>
        </div>

        <div id="form-gambar" data-section>
            <h3 class="text-[13px] font-bold uppercase tracking-wide text-brand mb-3">2.2 · Gambar Unggulan (Featured Image)</h3>
            <ul class="list-disc pl-5 space-y-2 text-sm text-slate-600 leading-relaxed">
                <li><strong class="text-slate-900">Format:</strong> JPG, JPEG, PNG, atau WEBP. Ukuran maksimal <strong class="text-brand">2 MB</strong> (diberlakukan sistem).</li>
                <li><strong class="text-slate-900">Resolusi disarankan:</strong> lebar <strong class="text-brand">≥ 1200px</strong> agar tajam di semua perangkat; WEBP lebih ringan untuk kecepatan halaman.</li>
                <li><strong class="text-slate-900">Alt Text</strong> wajib diisi (bernilai <strong class="text-brand">+5 poin</strong> SEO). Tulis deskripsi singkat &amp; natural, mis. <em>"Kantor pusat Wayang Group di Jakarta"</em> bukan keyword berulang.</li>
            </ul>
        </div>

        <div id="form-seo" data-section>
            <h3 class="text-[13px] font-bold uppercase tracking-wide text-brand mb-3">2.3 · Optimasi SEO &amp; Readability</h3>
            <p class="text-sm text-slate-600 leading-relaxed mb-4">
                Skor SEO dan Readability dihitung <strong class="text-slate-900">realtime</strong> saat mengetik dan
                <strong class="text-slate-900">diverifikasi ulang di server</strong> saat publish. Tab <em>SEO</em>
                menampilkan 13 indikator optimasi mesin pencari, tab <em>Readability</em> menampilkan 7 indikator
                keterbacaan konten. Kedua skor harus <strong class="text-brand">≥ 80</strong> untuk publikasi.
            </p>

            <div class="grid sm:grid-cols-2 gap-2.5 mb-5">
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5">
                    <p class="text-xs font-bold text-slate-800 mb-0.5">Focus Keyphrase</p>
                    <p class="text-[11px] text-slate-500">Tentukan <em>satu</em> kata kunci utama. Menilai kehadiran keyword di judul, heading, slug, dan kepadatan.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5">
                    <p class="text-xs font-bold text-slate-800 mb-0.5">SEO Title</p>
                    <p class="text-[11px] text-slate-500">Panjang ideal <strong class="text-brand">50–60 karakter</strong>. Kosongkan untuk memakai Judul Artikel.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5">
                    <p class="text-xs font-bold text-slate-800 mb-0.5">Meta Description</p>
                    <p class="text-[11px] text-slate-500">Ringkasan menarik <strong class="text-brand">120–156 karakter</strong> yang muncul di hasil pencarian.</p>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3.5">
                    <p class="text-xs font-bold text-slate-800 mb-0.5">URL Slug</p>
                    <p class="text-[11px] text-slate-500">Pendek, mengandung keyword, hanya huruf kecil, angka, dan strip. Auto dari judul, bisa diedit.</p>
                </div>
            </div>

            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Rincian Skor SEO (13 indikator)</p>
            <div class="overflow-x-auto rounded-lg border border-slate-200 mb-5">
                <table class="w-full text-left text-[12px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 text-[10px] uppercase tracking-wider">
                            <th class="px-3.5 py-2.5 font-bold">Indikator</th>
                            <th class="px-3.5 py-2.5 font-bold text-center">Poin</th>
                            <th class="px-3.5 py-2.5 font-bold">Cara Mendapatkan Skor Penuh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Title</td><td class="px-3.5 py-2.5 text-center text-brand">12</td><td class="px-3.5 py-2.5">Keyword ada di judul (bonus jika di awal)</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">SEO Title Length</td><td class="px-3.5 py-2.5 text-center text-brand">8</td><td class="px-3.5 py-2.5">Panjang 50–60 karakter</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Slug</td><td class="px-3.5 py-2.5 text-center text-brand">8</td><td class="px-3.5 py-2.5">Keyword ada di slug + format valid</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Introduction</td><td class="px-3.5 py-2.5 text-center text-brand">10</td><td class="px-3.5 py-2.5">Keyword ada di 100 kata pertama</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase Density</td><td class="px-3.5 py-2.5 text-center text-brand">8</td><td class="px-3.5 py-2.5">Kepadatan 0,5–2,5%</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Meta Desc</td><td class="px-3.5 py-2.5 text-center text-brand">10</td><td class="px-3.5 py-2.5">Keyword ada di meta description</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Meta Desc Length</td><td class="px-3.5 py-2.5 text-center text-brand">7</td><td class="px-3.5 py-2.5">Panjang 120–156 karakter</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Subheading</td><td class="px-3.5 py-2.5 text-center text-brand">9</td><td class="px-3.5 py-2.5">Keyword ada di H2/H3</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Keyphrase in Image Alt</td><td class="px-3.5 py-2.5 text-center text-brand">6</td><td class="px-3.5 py-2.5">Keyword ada di alt text gambar</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Internal Links</td><td class="px-3.5 py-2.5 text-center text-brand">8</td><td class="px-3.5 py-2.5">Minimal 1 tautan internal — format: <code class="text-[10px] bg-slate-100 px-1 rounded">/slug</code>, <code class="text-[10px] bg-slate-100 px-1 rounded">slug</code>, atau <code class="text-[10px] bg-slate-100 px-1 rounded">https://domainmu.com/slug</code></td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Outbound Links</td><td class="px-3.5 py-2.5 text-center text-brand">5</td><td class="px-3.5 py-2.5">Minimal 1 referensi eksternal (http)</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Content Word Count</td><td class="px-3.5 py-2.5 text-center text-brand">9</td><td class="px-3.5 py-2.5">Minimal 900 kata</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Previously Used Keyphrase</td><td class="px-3.5 py-2.5 text-center text-brand">8</td><td class="px-3.5 py-2.5">Keyword belum dipakai artikel lain</td></tr>
                        <tr class="bg-brand/10">
                            <td class="px-3.5 py-2.5 font-bold text-slate-900">Total Maksimal</td>
                            <td class="px-3.5 py-2.5 text-center font-black text-brand">100</td>
                            <td class="px-3.5 py-2.5 text-slate-600">Skor publikasi minimal <strong class="text-brand">80</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Rincian Skor Readability (7 indikator)</p>
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full text-left text-[12px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 text-[10px] uppercase tracking-wider">
                            <th class="px-3.5 py-2.5 font-bold">Indikator</th>
                            <th class="px-3.5 py-2.5 font-bold text-center">Poin</th>
                            <th class="px-3.5 py-2.5 font-bold">Cara Mendapatkan Skor Penuh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Paragraph Length</td><td class="px-3.5 py-2.5 text-center text-brand">15</td><td class="px-3.5 py-2.5">≤ 10% paragraf lebih dari 150 kata</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Sentence Length</td><td class="px-3.5 py-2.5 text-center text-brand">18</td><td class="px-3.5 py-2.5">≤ 25% kalimat lebih dari 20 kata</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Subheading Distribution</td><td class="px-3.5 py-2.5 text-center text-brand">15</td><td class="px-3.5 py-2.5">Rata-rata ≤ 300 kata antar subheading</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Transition Words</td><td class="px-3.5 py-2.5 text-center text-brand">15</td><td class="px-3.5 py-2.5">≥ 30% kalimat pakai kata penghubung</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Passive Voice</td><td class="px-3.5 py-2.5 text-center text-brand">12</td><td class="px-3.5 py-2.5">≤ 10% kalimat pasif</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Consecutive Sentences</td><td class="px-3.5 py-2.5 text-center text-brand">12</td><td class="px-3.5 py-2.5">≤ 2 kalimat berturut awalnya sama</td></tr>
                        <tr><td class="px-3.5 py-2.5 text-slate-800">Flesch Reading Ease</td><td class="px-3.5 py-2.5 text-center text-brand">13</td><td class="px-3.5 py-2.5">Skor 50–70 (target 60)</td></tr>
                        <tr class="bg-brand/10">
                            <td class="px-3.5 py-2.5 font-bold text-slate-900">Total Maksimal</td>
                            <td class="px-3.5 py-2.5 text-center font-black text-brand">100</td>
                            <td class="px-3.5 py-2.5 text-slate-600">Skor publikasi minimal <strong class="text-brand">80</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3.5">
                <p class="text-xs font-bold text-amber-800 mb-1">⚠ Threshold Ganda</p>
                <p class="text-[11px] text-amber-700">Artikel hanya bisa dipublikasikan jika <strong>kedua skor</strong> (SEO dan Readability) mencapai minimal <strong>80 dari 100</strong>. Jika salah satu masih di bawah 80, tombol Publish akan nonaktif.</p>
            </div>
        </div>

        <div id="form-kategori" data-section>
            <h3 class="text-[13px] font-bold uppercase tracking-wide text-brand mb-3">2.4 · Kategori, Tag, &amp; Situs WordPress</h3>
            <ul class="list-disc pl-5 space-y-2 text-sm text-slate-600 leading-relaxed">
                <li><strong class="text-slate-900">Kategori</strong> wajib memilih <strong class="text-brand">minimal 1</strong>. Kategori tersedia sesuai perusahaan aktif. Memilih kategori akan <em>otomatis menandai</em> situs WordPress yang terhubung dengan kategori tersebut.</li>
                <li><strong class="text-slate-900">Situs WordPress</strong> wajib memilih <strong class="text-brand">minimal 1</strong> situs target. Anda tetap bisa menyesuaikan pilihan situs secara manual meski sudah tersinkron dari kategori.</li>
                <li><strong class="text-slate-900">Tag</strong> opsional, ketik nama tag lalu tekan <kbd class="text-brand bg-slate-100 px-1 py-0.5 rounded text-[12px]">Enter</kbd> untuk menambah chip. Maksimal 50 karakter per tag.</li>
                <li><strong class="text-slate-900">Author</strong> default adalah akun Anda. Admin &amp; super admin dapat memilih author lain; untuk role author, author terkunci pada akun yang sedang login.</li>
            </ul>
            <div class="mt-3 bg-blue-50 border border-blue-200 rounded-lg p-3.5">
                <p class="text-xs font-bold text-blue-800 mb-1">ℹ️ Format Username WordPress</p>
                <p class="text-[11px] text-blue-700">Username di portal <strong>harus persis sama</strong> dengan username di WordPress (termasuk spasi dan huruf besar/kecil). Sistem sinkronisasi author menggunakan <strong>exact match</strong> — jika ada perbedaan, author tidak akan terhubung ke akun WordPress yang benar. Contoh: <code class="bg-blue-100 px-1 rounded">Rizki Aulia</code> di portal harus sama persis dengan <code class="bg-blue-100 px-1 rounded">Rizki Aulia</code> di WordPress.</p>
            </div>
        </div>
    </div>
</section>

{{-- ===== 3. DO'S & DON'TS ===== --}}
<section id="dos-donts" data-section class="scroll-mt-24 bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
    <header class="mb-5">
        <h2 class="flex items-baseline gap-2.5 font-sans text-lg font-bold text-slate-900">
            <span class="text-sm font-black text-brand">03</span> Do's &amp; Don'ts
        </h2>
        <p class="mt-1 text-sm text-slate-500">Kebiasaan yang dianjurkan vs. yang harus dihindari.</p>
    </header>

    <div class="grid md:grid-cols-2 gap-5">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600 mb-3">Yang Dianjurkan</p>
            <ul class="space-y-2 text-sm text-slate-600 leading-relaxed">
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Tulis konten orisinal &amp; berdasarkan fakta.</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Gunakan gambar berkualitas + alt text deskriptif.</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Strukturkan artikel dengan H2/H3 yang rapi.</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Pakai satu focus keyword dengan kepadatan wajar (1–2,5%).</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Sertakan internal &amp; external link yang relevan.</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Tulis meta description 120–156 karakter yang mengundang klik.</li>
                <li class="flex gap-2.5"><span class="text-emerald-600 shrink-0">✓</span> Review &amp; baca ulang sebelum publish.</li>
            </ul>
        </div>

        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-rose-600 mb-3">Yang Dilarang</p>
            <ul class="space-y-2 text-sm text-slate-600 leading-relaxed">
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Plagiarisme / menyalin artikel orang lain.</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Keyword stuffing (memenuhi konten dengan keyword berulang).</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Spam link / tautan yang tidak relevan.</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Gambar pecah, resolusi rendah, atau tanpa alt text.</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Judul clickbait yang tidak sesuai isi.</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Publish sebelum skor SEO mencapai 80.</li>
                <li class="flex gap-2.5"><span class="text-rose-600 shrink-0">✕</span> Publish tanpa kategori / situs target.</li>
            </ul>
        </div>
    </div>
</section>

{{-- ===== 4. FAQ ===== --}}
<section id="faq" data-section class="scroll-mt-24 bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
    <header class="mb-5">
        <h2 class="flex items-baseline gap-2.5 font-sans text-lg font-bold text-slate-900">
            <span class="text-sm font-black text-brand">04</span> FAQ Ringkas
        </h2>
        <p class="mt-1 text-sm text-slate-500">Pertanyaan yang paling sering diajukan penulis.</p>
    </header>

    <div class="space-y-3">
        @foreach ([
            'Kenapa tombol Publish tidak bisa diklik?' => 'Karena skor SEO Anda masih di bawah <strong class="text-brand">80</strong>. Buka panel <em>Optimasi SEO & Google Preview</em>, perbaiki indikator yang belum penuh (rincian di Bagian 2.3), lalu coba lagi.',
            'Kenapa status artikel lama di "Antrean"?' => 'Publikasi diproses <em>satu per satu per situs</em> melalui antrean (queue). Status "Antrean" berarti artikel sedang dikirim ke WordPress tunggu beberapa saat lalu muat ulang halaman.',
            'Artikel saya statusnya "Gagal". Bagaimana memperbaikinya?' => 'Klik <em>Retry</em> untuk mengirim ulang situs yang gagal. Bila gagal berulang, periksa kredensial WordPress situs tersebut (username & application password) atau hubungi admin.',
            'Bolehkah mengubah author artikel?' => 'Untuk role <strong class="text-slate-900">author</strong>, author terkunci pada akun yang sedang login. Admin & super admin dapat memilih author lain saat membuat atau mengedit artikel.',
            'Gambar dan format apa yang didukung?' => 'Gambar unggulan mendukung <strong class="text-slate-900">JPG, JPEG, PNG, dan WEBP</strong> dengan ukuran maksimal <strong class="text-brand">2 MB</strong>. Disarankan lebar ≥ 1200px agar tampil tajam sebagai banner.',
        ] as $faqQuestion => $faqAnswer)
            <details class="group rounded-lg border border-slate-200 bg-slate-50/60 open:border-brand/40 transition">
                <summary class="flex items-center justify-between gap-3 cursor-pointer px-4 py-3.5 text-sm font-semibold text-slate-800 list-none">
                    {{ $faqQuestion }}
                    <svg class="w-4 h-4 text-slate-400 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </summary>
                <p class="px-4 pb-4 text-[13px] text-slate-600 leading-relaxed">{!! $faqAnswer !!}</p>
            </details>
        @endforeach
    </div>
</section>
