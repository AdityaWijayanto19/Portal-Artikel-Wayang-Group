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

{{-- ===== 5. PANDUAN DETAIL SEO & READABILITY ===== --}}
<section id="detail-seo-readability" data-section class="scroll-mt-24 bg-white border border-slate-200 rounded-xl p-6 sm:p-8">
    <header class="mb-5">
        <h2 class="flex items-baseline gap-2.5 font-sans text-lg font-bold text-slate-900">
            <span class="text-sm font-black text-brand">05</span> Panduan Detail SEO &amp; Readability
        </h2>
        <p class="mt-1 text-sm text-slate-500">Penjelasan lengkap setiap indikator beserta contoh penulisan yang benar dan salah.</p>
    </header>

    {{-- ==================== SEO INDICATORS ==================== --}}
    <div class="mb-8">
        <h3 id="detail-seo" data-section class="scroll-mt-24 text-sm font-black uppercase tracking-wider text-brand mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-brand/10 border border-brand/30 text-[10px] font-black flex items-center justify-center">SEO</span>
            SEO Indicators — 13 Indikator (Total 100 Poin)
        </h3>

        <div class="space-y-6">

            {{-- 5.1 Keyphrase in Title --}}
            <div id="detail-seo-1" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.1</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Title</h4>
                    <span class="ml-auto text-xs font-black text-brand">12 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Focus keyphrase harus muncul di SEO Title. Skor penuh jika keyword berada di <strong>awal judul</strong>.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (12 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Artikel SEO Terpusat: Panduan Lengkap untuk Holding

Artikel SEO Terpusat adalah strategi...</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Panduan Lengkap untuk Perusahaan Holding

Tidak ada keyword "artikel seo terpusat"
di judul sama sekali.</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Tempatkan keyword di awal judul, misalnya "Artikel SEO Terpusat: ..." bukan "...: Artikel SEO Terpusat".
                </div>
            </div>

            {{-- 5.2 SEO Title Length --}}
            <div id="detail-seo-2" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.2</span>
                    <h4 class="text-sm font-bold text-slate-900">SEO Title Length</h4>
                    <span class="ml-auto text-xs font-black text-brand">8 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Panjang SEO Title idealnya <strong>50–60 karakter</strong>. Terlalu pendek kurang informatif, terlalu panjang terpotong di hasil pencarian Google.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — 50-60 karakter (8 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Strategi Artikel SEO Terpusat untuk Bisnis
(43 karakter — masuk range ideal)</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Terlalu panjang (3 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Ini Adalah Judul Artikel yang Sangat Panjang
Sekali untuk Optimasi SEO yang Baik (85 karakter)

→ Terpotong di Google, user tidak lihat full title</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Gunakan ruler/counter saat menulis judul. Idealnya 50-60 karakter. Hindari judul < 35 karakter.
                </div>
            </div>

            {{-- 5.3 Keyphrase in Slug --}}
            <div id="detail-seo-3" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.3</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Slug</h4>
                    <span class="ml-auto text-xs font-black text-brand">8 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Slug (URL) harus mengandung focus keyphrase + hanya boleh huruf kecil, angka, dan strip <code>-</code>.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (8 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>/artikel-seo-terpusat-panduan

→ Keyword ada di slug
→ Hanya huruf kecil, angka, strip</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (2 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>/panduan_untuk_bisnis_2026

→ Keyword tidak ada di slug
→ Pakai underscore, bukan strip</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Slug ideal: <code>keyword-utama-sub-judul</code>. Hindari spasi, underscore, atau karakter khusus.
                </div>
            </div>

            {{-- 5.4 Keyphrase in Introduction --}}
            <div id="detail-seo-4" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.4</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Introduction</h4>
                    <span class="ml-auto text-xs font-black text-brand">10 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Focus keyphrase harus muncul di <strong>100 kata pertama</strong> artikel (paragraf pembuka).</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (10 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code><strong>Artikel SEO terpusat</strong> adalah strategi
pengelolaan konten yang memungkinkan
holding mengoptimasi seluruh anak
perusahaan secara seragam...

→ Keyword muncul di kalimat pertama</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Dalam dunia digital marketing, ada banyak
strategi yang bisa digunakan untuk
 meningkatkan visibilitas online. Salah
satunya adalah dengan menggunakan...

→ Keyword baru muncul di paragraf ke-5</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Sebutkan keyword di kalimat pertama atau kedua paragraf pembuka. Jangan "basa-basi" terlalu panjang sebelum masuk topik.
                </div>
            </div>

            {{-- 5.5 Keyphrase Density --}}
            <div id="detail-seo-5" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.5</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase Density</h4>
                    <span class="ml-auto text-xs font-black text-brand">8 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Kepadatan keyword idealnya <strong>0.5–2.5%</strong> dari total kata. Terlalu rendah = tidak relevan. Terlalu tinggi = keyword stuffing.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — 1.2% density (8 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Konten 1000 kata, keyword muncul 12 kali
= 1.2% density (ideal)

Contoh penyebaran natural:
- Paragraf 1: 1x
- Paragraf 2: 2x
- Subheading: 1x
- Paragraf 3-5: masing-masing 2x
- Kesimpulan: 2x</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — 3.5% density (4 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Konten 1000 kata, keyword muncul 35 kali
= 3.5% density (keyword stuffing!)

Ini adalah artikel tentang artikel SEO terpusat.
Artikel SEO terpusat sangat penting. Dengan
artikel SEO terpusat, kita bisa...

→ Google menganggap ini spam/keyword stuffing</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Gunakan sinonim dan variasi. Contoh: "artikel SEO terpusat", "strategi SEO terpusat", "konten SEO terpusat" — tidak harus persis sama setiap kali.
                </div>
            </div>

            {{-- 5.6 Keyphrase in Meta Description --}}
            <div id="detail-seo-6" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.6</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Meta Description</h4>
                    <span class="ml-auto text-xs font-black text-brand">10 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Focus keyphrase harus muncul di meta description yang muncul di hasil pencarian Google.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (10 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Pelajari strategi <strong>artikel SEO terpusat</strong>
untuk mengelola konten lintas holding
dengan kualitas terukur.

→ Keyword ada di meta description</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Panduan lengkap untuk mengelola konten
digital perusahaan Anda dengan baik dan
benar.

→ Keyword "artikel SEO terpusat" tidak ada</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Meta description adalah "iklan gratis" di Google. Buat menarik + mengandung keyword agar user mau klik.
                </div>
            </div>

            {{-- 5.7 Meta Description Length --}}
            <div id="detail-seo-7" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.7</span>
                    <h4 class="text-sm font-bold text-slate-900">Meta Description Length</h4>
                    <span class="ml-auto text-xs font-black text-brand">7 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Panjang meta description idealnya <strong>120–156 karakter</strong>. Google memotong jika terlalu panjang.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — 130 karakter (7 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Pelajari strategi artikel SEO terpusat
untuk mengelola konten lintas holding
dengan kualitas terukur.
(130 karakter — ideal!)</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Terlalu pendek (2 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Panduan SEO.
(11 karakter — terlalu pendek)

Atau terlalu panjang:
Panduan lengkap tentang bagaimana cara
mengelola artikel SEO terpusat untuk
perusahaan holding dengan anak
perusahaan yang banyak...
(180+ karakter — terpotong di Google)</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Tulis meta description seperti copywriting iklan: ringkas, informatif, mengundang klik. Ideal 120-156 karakter.
                </div>
            </div>

            {{-- 5.8 Keyphrase in Subheading --}}
            <div id="detail-seo-8" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.8</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Subheading</h4>
                    <span class="ml-auto text-xs font-black text-brand">9 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Focus keyphrase harus muncul di minimal 1 tag <strong>H2 atau H3</strong> dalam konten.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (9 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;h2&gt;Strategi &lt;strong&gt;Artikel SEO Terpusat&lt;/strong&gt;
dalam Praktik&lt;/h2&gt;

&lt;h3&gt;Manfaat &lt;strong&gt;Artikel SEO Terpusat&lt;/strong&gt;
untuk Bisnis&lt;/h3&gt;

→ Keyword muncul di subheading H2 dan H3</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;h2&gt;Strategi dalam Praktik&lt;/h2&gt;

&lt;h3&gt;Manfaat untuk Bisnis&lt;/h3&gt;

→ Keyword tidak ada di subheading manapun</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Masukkan keyword di minimal 1 subheading H2. Bisa juga di H3 untuk poin tambahan.
                </div>
            </div>

            {{-- 5.9 Keyphrase in Image Alt --}}
            <div id="detail-seo-9" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.9</span>
                    <h4 class="text-sm font-bold text-slate-900">Keyphrase in Image Alt</h4>
                    <span class="ml-auto text-xs font-black text-brand">6 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Alt text gambar harus mengandung focus keyphrase + bersifat deskriptif. Alt text kosong = 0 poin.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (6 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;img alt="Ilustrasi artikel SEO terpusat
untuk strategi konten holding"
src="gambar.jpg"&gt;

→ Alt text deskriptif + mengandung keyword</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;img alt="" src="gambar.jpg"&gt;
→ Alt text kosong (0 poin)

Atau:
&lt;img src="gambar.jpg"&gt;
→ Tidak ada alt text sama sekali (0 poin)</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Alt text harus deskriptif tentang gambar, bukan hanya keyword. Contoh: "Tim editorial sedang menyusun artikel SEO terpusat".
                </div>
            </div>

            {{-- 5.10 Internal Links --}}
            <div id="detail-seo-10" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.10</span>
                    <h4 class="text-sm font-bold text-slate-900">Internal Links</h4>
                    <span class="ml-auto text-xs font-black text-brand">8 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Artikel harus memiliki minimal <strong>1 tautan internal</strong> (link ke halaman lain di situs yang sama).</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (8 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;a href="/blog/strategi-seo"&gt;
  Baca juga: Strategi SEO
&lt;/a&gt;

Atau:
&lt;a href="https://domainmu.com/kategori/seo"&gt;
  Artikel terkait
&lt;/a&gt;

→ Link ke halaman sendiri (internal)</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Tidak ada tautan internal sama sekali
dalam artikel.

Atau semua link hanya ke situs eksternal:
&lt;a href="https://google.com"&gt;Google&lt;/a&gt;
&lt;a href="https://wikipedia.org"&gt;Wiki&lt;/a&gt;

→ Tidak ada internal link</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Link ke artikel lain di situs yang sama. Misal: "Baca juga panduan SEO lainnya di /blog/panduan-seo". Format: <code>/slug</code> atau <code>https://domainmu.com/slug</code>.
                </div>
            </div>

            {{-- 5.11 Outbound Links --}}
            <div id="detail-seo-11" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.11</span>
                    <h4 class="text-sm font-bold text-slate-900">Outbound Links</h4>
                    <span class="ml-auto text-xs font-black text-brand">5 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Artikel harus memiliki minimal <strong>1 tautan eksternal</strong> (link ke situs lain) sebagai referensi.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar (5 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>&lt;a href="https://developers.google.com/search"&gt;
  Google Search Central
&lt;/a&gt;

→ Link ke situs eksternal (http/https)</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Tidak ada tautan eksternal sama sekali
dalam artikel.

→ Tidak ada referensi dari situs lain</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Link ke sumber terpercaya: Google Documentation, Wikipedia, atau situs otoritatif lainnya. Relevan dengan topik artikel.
                </div>
            </div>

            {{-- 5.12 Content Word Count --}}
            <div id="detail-seo-12" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.12</span>
                    <h4 class="text-sm font-bold text-slate-900">Content Word Count</h4>
                    <span class="ml-auto text-xs font-black text-brand">9 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Jumlah kata konten minimal <strong>900 kata</strong> untuk skor penuh. Konten pendek dianggap kurang komprehensif.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — 1200 kata (9 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Artikel dengan 1200+ kata

~ 15-20 paragraf
~ 3-5 subheading
~ Waktu baca: 6 menit

→ Konten komprehensif dan mendalam</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — 150 kata (2 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Artikel hanya 150 kata

~ 2-3 paragraf saja
~ Tidak ada subheading
~ Waktu baca: 30 detik

→ Terlalu pendek, tidak komprehensif</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Target minimal 900 kata, idealnya 1200-1500 kata untuk artikel komprehensif. Gunakan outline sebelum menulis agar konten terstruktur dan cukup panjang.
                </div>
            </div>

            {{-- 5.13 Previously Used Keyphrase --}}
            <div id="detail-seo-13" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.13</span>
                    <h4 class="text-sm font-bold text-slate-900">Previously Used Keyphrase</h4>
                    <span class="ml-auto text-xs font-black text-brand">8 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Focus keyphrase harus <strong>unik</strong> — belum pernah dipakai di artikel lain dalam sistem.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Keyword unik (8 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Focus keyword: "artikel seo terpusat"

→ Belum ada artikel lain dengan keyword ini
→ Keyword unik untuk artikel ini</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Keyword duplikat (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Focus keyword: "strategi digital marketing"

→ Sudah dipakai 3 artikel lain
→ Keyword tidak unik, bisa kanibal SEO

Solusi: Gunakan variasi seperti
"strategi digital marketing b2b" atau
"tips digital marketing untuk UMKM"</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Cek keyword yang sudah dipakai sebelum menulis. Gunakan variasi spesifik: tambahkan lokasi, tahun, atau niche tertentu. Contoh: "strategi SEO 2026 untuk e-commerce".
                </div>
            </div>

        </div>
    </div>

    {{-- ==================== READABILITY INDICATORS ==================== --}}
    <div>
        <h3 id="detail-readability" data-section class="scroll-mt-24 text-sm font-black uppercase tracking-wider text-brand mb-4 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-brand/10 border border-brand/30 text-[10px] font-black flex items-center justify-center">R</span>
            Readability Indicators — 7 Indikator (Total 100 Poin)
        </h3>

        <div class="space-y-6">

            {{-- 5.14 Paragraph Length --}}
            <div id="detail-read-1" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.14</span>
                    <h4 class="text-sm font-bold text-slate-900">Paragraph Length</h4>
                    <span class="ml-auto text-xs font-black text-brand">15 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Maksimal <strong>10% paragraf</strong> boleh memiliki lebih dari 150 kata. Paragraf panjang sulit dibaca di layar.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Paragraf pendek (15 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Paragraf 1: 60 kata ✓
Paragraf 2: 80 kata ✓
Paragraf 3: 45 kata ✓
Paragraf 4: 70 kata ✓
Paragraf 5: 55 kata ✓

→ 0% paragraf panjang (semua < 150 kata)
→ Mudah dibaca, nyaman di mata</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Paragraf panjang (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Paragraf 1: 350 kata ✗
Paragraf 2: 280 kata ✗
Paragraf 3: 60 kata ✓
Paragraf 4: 400 kata ✗

→ 75% paragraf panjang (> 150 kata)
→ Pembaca kelelahan, bounce rate tinggi</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Pecah paragraf panjang menjadi 2-3 paragraf pendek. Idealnya 50-100 kata per paragraf. Setiap paragraf = 1 gagasan.
                </div>
            </div>

            {{-- 5.15 Sentence Length --}}
            <div id="detail-read-2" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.15</span>
                    <h4 class="text-sm font-bold text-slate-900">Sentence Length</h4>
                    <span class="ml-auto text-xs font-black text-brand">18 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Maksimal <strong>25% kalimat</strong> boleh memiliki lebih dari 20 kata. Kalimat panjang sulit dipahami.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Kalimat pendek (18 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"Strategi ini membantu perusahaan
mengelola konten dengan lebih
efisien." (12 kata) ✓

"Hasilnya, visibilitas online meningkat
signifikan." (8 kata) ✓

→ Ringkas, langsung ke poin</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Kalimat panjang (6 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"Strategi ini, yang dikembangkan oleh tim
digital marketing sejak tahun 2020 dan
telah diuji coba di berbagai perusahaan
dengan berbagai ukuran mulai dari UMKM
hingga korporasi besar, terbukti membantu
meningkatkan visibilitas online." (42 kata) ✗

→ Terlalu panjang, melelahkan</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Idealnya 10-20 kata per kalimat. Jika kalimat > 30 kata, pecah menjadi 2 kalimat. Gunakan tanda titik lebih sering.
                </div>
            </div>

            {{-- 5.16 Subheading Distribution --}}
            <div id="detail-read-3" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.16</span>
                    <h4 class="text-sm font-bold text-slate-900">Subheading Distribution</h4>
                    <span class="ml-auto text-xs font-black text-brand">15 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Rata-rata <strong>≤ 300 kata</strong> antar subheading (H2/H3). Membantu pembaca scan dan navigasi konten.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Distribusi merata (15 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Artikel 1200 kata dengan 5 subheading:

H2: Pengenalan (200 kata)
H2: Manfaat (250 kata)
H2: Cara Implementasi (300 kata)
H2: Studi Kasus (250 kata)
H2: Kesimpulan (200 kata)

→ Rata-rata 240 kata antar subheading ✓</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Subheading jarang (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Artikel 1500 kata dengan 1 subheading:

H2: Pengenalan (50 kata)
    [Tanpa subheading - 1400 kata teks]
H2: Kesimpulan (50 kata)

→ Rata-rata 1500 kata antar subheading ✗
→ Pembaca kesulitan mencari informasi</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Gunakan H2 untuk topik utama, H3 untuk sub-bagian. Idealnya 1 subheading setiap 200-300 kata. Buat outline sebelum menulis.
                </div>
            </div>

            {{-- 5.17 Transition Words --}}
            <div id="detail-read-4" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.17</span>
                    <h4 class="text-sm font-bold text-slate-900">Transition Words</h4>
                    <span class="ml-auto text-xs font-black text-brand">15 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Minimal <strong>30% kalimat</strong> harus menggunakan kata penghubung (transition words) agar tulisan mengalir.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Banyak transition (15 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"<strong>Selain itu</strong>, strategi ini juga efisien.
<strong>Namun</strong>, implementasinya membutuhkan waktu.
<strong>Oleh karena itu</strong>, perlu perencanaan matang.
<strong>Contohnya</strong>, PT X berhasil meningkatkan...
<strong>Pertama</strong>, kita perlu memahami...
<strong>Kemudian</strong>, langkah selanjutnya adalah..."

→ 6/8 kalimat pakai transition = 75%</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Tanpa transition (0 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"Strategi ini efisien. Implementasinya
membutuhkan waktu. Perlu perencanaan
matang. PT X berhasil meningkatkan.
Kita perlu memahami. Langkah selanjutnya
adalah..."

→ 0/8 kalimat pakai transition = 0%
→ Tulisan terasa kaku dan tidak mengalir</code></pre>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-[12px] text-amber-700 mb-3">
                    <strong>Kata Penghubung yang Direkomendasikan:</strong><br>
                    <strong>Addition:</strong> selain itu, di samping itu, furthermore, moreover<br>
                    <strong>Contrast:</strong> namun, tetapi, akan tetapi, sebaliknya<br>
                    <strong>Cause:</strong> karena, sebab, oleh karena itu, akibatnya<br>
                    <strong>Example:</strong> misalnya, contohnya, yaitu, seperti<br>
                    <strong>Sequence:</strong> pertama, kedua, selanjutnya, kemudian, akhirnya<br>
                    <strong>Conclusion:</strong> singkatnya, intinya, pada dasarnya, dengan demikian
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Variasikan penggunaan transition words. Jangan pakai "namun" terus-menerus. Campur: "selain itu", "sebaliknya", "akibatnya", dll.
                </div>
            </div>

            {{-- 5.18 Passive Voice --}}
            <div id="detail-read-5" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.18</span>
                    <h4 class="text-sm font-bold text-slate-900">Passive Voice</h4>
                    <span class="ml-auto text-xs font-black text-brand">12 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Maksimal <strong>10% kalimat</strong> boleh menggunakan voice pasif. Kalimat aktif lebih engaging dan jelas.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Aktif (12 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"<strong>Tim mengelola</strong> artikel SEO terpusat."

→ Subjek (Tim) melakukan aksi (mengelola)
→ Jelas, langsung, engaging

"<strong>Kami mengembangkan</strong> strategi baru."

→ Voice aktif, mudah dipahami</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Pasif (4 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"Artikel SEO terpusat <strong>dikelola</strong> oleh tim."

→ Pola "di-..." = passive voice

"Strategi baru <strong>dikembangkan</strong> oleh kami."

→ Pola "di-..." = passive voice

Ciri passive voice Bahasa Indonesia:
awalan di-, ter-, akhiran -kan, -i</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Tulis dengan subjek aktif. Ubah "Artikel ditulis oleh penulis" → "Penulis menulis artikel". Hindari berlebihan pakai "di-" dan "ter-".
                </div>
            </div>

            {{-- 5.19 Consecutive Sentences --}}
            <div id="detail-read-6" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.19</span>
                    <h4 class="text-sm font-bold text-slate-900">Consecutive Sentences</h4>
                    <span class="ml-auto text-xs font-black text-brand">12 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Maksimal <strong>2 kalimat berturut-turut</strong> diawali kata yang sama. Variasi awal kalimat agar tulisan tidak monoton.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Variasi awal (12 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"<strong>Pertama</strong>, buat outline konten.
<strong>Kemudian</strong>, tulis draf pertama.
<strong>Setelah itu</strong>, review dan perbaiki.
<strong>Terakhir</strong>, publikasikan ke WordPress."

→ Setiap kalimat diawali kata berbeda
→ Variasi, tidak membosankan</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Awal sama (4 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>"<strong>Artikel</strong> ini membahas SEO.
<strong>Artikel</strong> ini juga membahas Readability.
<strong>Artikel</strong> ini cocok untuk pemula.
<strong>Artikel</strong> ini sangat informatif."

→ 4 kalimat berturut diawali "Artikel"
→ Monoton, membosankan, skor turun</code></pre>
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Mulai kalimat dengan variasi: nama orang/tempat, angka, kata sifat, kata kerja, atau transition words. Hindari pola berulang.
                </div>
            </div>

            {{-- 5.20 Flesch Reading Ease --}}
            <div id="detail-read-7" data-section class="scroll-mt-24 bg-slate-50/60 border border-slate-200 rounded-lg p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-black text-brand bg-brand/10 px-2 py-0.5 rounded">5.20</span>
                    <h4 class="text-sm font-bold text-slate-900">Flesch Reading Ease</h4>
                    <span class="ml-auto text-xs font-black text-brand">13 poin</span>
                </div>
                <p class="text-[13px] text-slate-600 leading-relaxed mb-3">Skor Flesch Reading Ease targetnya <strong>50–70</strong> (target 60). Mengukur seberapa mudah konten dibaca berdasarkan panjang kalimat dan suku kata.</p>
                <div class="grid sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-600 uppercase mb-1">✅ Benar — Skor 60 (13 poin)</p>
                        <pre class="bg-emerald-50 border border-emerald-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Skor Flesch: 60.0 (target ideal)

Ciri-ciri tulisan mudah dibaca:
- Kalimat pendek (10-20 kata)
- Kata-kata sederhana
- Tidak terlalu banyak istilah teknis
- Paragraf pendek

→ Mudah dipahami orang awam</code></pre>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-rose-600 uppercase mb-1">❌ Salah — Skor 25 (3 poin)</p>
                        <pre class="bg-rose-50 border border-rose-200 rounded p-3 text-[12px] text-slate-700 overflow-x-auto"><code>Skor Flesch: 25.0 (terlalu kompleks)

Ciri-ciri tulisan sulit dibaca:
- Kalimat sangat panjang (40+ kata)
- Banyak istilah teknis
- Suku kata panjang-banyak
- Tidak ada jeda

→ Hanya bisa dipahami ahli</code></pre>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-[12px] text-amber-700 mb-3">
                    <strong>Skala Flesch Reading Ease:</strong><br>
                    <strong>90-100:</strong> Sangat mudah (anak SD)<br>
                    <strong>80-89:</strong> Mudah (konversasi sehari-hari)<br>
                    <strong>70-79:</strong> Cukup mudah<br>
                    <strong>60-69:</strong> Standar (target ideal!)<br>
                    <strong>50-59:</strong> Cukup sulit<br>
                    <strong>30-49:</strong> Sulit (tingkat universitas)<br>
                    <strong>0-29:</strong> Sangat sulit (akademis)
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 text-[12px] text-blue-700">
                    💡 <strong>Tips:</strong> Gunakan kalimat pendek + kata sederhana. Hindari istilah teknis berlebihan. Jika harus pakai istilah teknis, jelaskan dalam bahasa sederhana.
                </div>
            </div>

        </div>
    </div>

    {{-- Summary Box --}}
    <div class="mt-8 bg-gradient-to-r from-brand/5 to-blue-50 border border-brand/20 rounded-xl p-5">
        <h4 class="text-sm font-bold text-slate-900 mb-3">Ringkasan Threshold Ganda</h4>
        <div class="grid sm:grid-cols-3 gap-3">
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-center">
                <p class="text-lg font-black text-emerald-600">≥ 80</p>
                <p class="text-[11px] text-emerald-700 font-semibold">SEO + Readability</p>
                <p class="text-[10px] text-emerald-600 mt-1">✓ Bisa Publish</p>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
                <p class="text-lg font-black text-amber-600">60 – 79</p>
                <p class="text-[11px] text-amber-700 font-semibold"> salah satu skor</p>
                <p class="text-[10px] text-amber-600 mt-1">⚠ Needs Improvement</p>
            </div>
            <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-center">
                <p class="text-lg font-black text-rose-600">< 60</p>
                <p class="text-[11px] text-rose-700 font-semibold">salah satu skor</p>
                <p class="text-[10px] text-rose-600 mt-1">✕ Tidak Bisa Publish</p>
            </div>
        </div>
    </div>
</section>
