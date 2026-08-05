# Aturan Kode, Bisnis, dan Keamanan (RULES.md)

## 1. Aturan Bisnis & Gerbang Publikasi (Publish Gatekeeper)
1. **Validasi Sisi Server (Strict Server-Side Guard):**
   * Controller dan Service Laravel **WAJIB MENOLAK** proses publikasi artikel jika nilai `seo_score` kurang dari **80**, meskipun permintaan API atau Form dikirim secara manual/dipintas dari sisi klien.
2. **Aturan Status Artikel Berdasarkan Skor:**
   * **Skor >= 80 (🟢 Good):** Status artikel diizinkan berubah menjadi `queued` / `published`. Tombol `[Publish]` pada UI diaktifkan dengan aksen warna utama.
   * **Skor 60 - 79 (🟡 Needs Improvement):** Status artikel dikunci pada `draft`. Pengguna hanya diizinkan menggunakan tombol `[Save Draft]`. Tombol `[Publish]` dinonaktifkan (`disabled/muted`).
   * **Skor < 60 (🔴 Poor):** Status artikel dikunci pada `draft`. Tombol `[Publish]` dinonaktifkan (`disabled/muted`). UI wajib menampilkan panel daftar rekomendasi aksi perbaikan.

---

## 2. Rumus & Bobot Evaluasi SEO Realtime (100 Points Scale)
Kalkulasi skor SEO pada Engine Realtime (Alpine.js) dan Validasi Backend Laravel dihitung berdasarkan 12 komponen dengan total akumulasi **100 Poin**:

1. **SEO Title = 15 Point** (Panjang karakter ideal 50–60 karakter).
2. **Meta Description = 10 Point** (Panjang karakter ideal 120–156 karakter).
3. **URL Slug = 10 Point** (Mengandung kata kunci fokus & ramah URL).
4. **Focus Keyword = 10 Point** (Focus keyword terisi & valid).
5. **Keyword di Title = 10 Point** (Focus keyword berada pada SEO Title).
6. **Keyword di Heading (H1/H2) = 10 Point** (Focus keyword ditemukan dalam struktur heading).
7. **Keyword Density = 10 Point** (Kepadatan kata kunci berada pada rentang ideal 1% - 2.5%).
8. **Internal Link = 10 Point** (Minimal terdapat 1 tautan internal ke situs web terkait).
9. **External Link = 5 Point** (Minimal terdapat 1 tautan eksternal bereputasi).
10. **Alt Image = 5 Point** (Gambar Utama memiliki atribut Alt Text yang terisi).
11. **Content Length = 10 Point** (Panjang konten minimal 800 kata).
12. **Readability = 5 Point** (Keterbacaan dan kejelasan struktur paragraf).

---

## 3. Aturan Injeksi Meta Key Yoast SEO (Inikator Otomatis WP)
Untuk mencegah indikator SEO di daftar artikel WordPress (`edit.php`) bernilai *"Not Available"* (abu-abu), setiap request publikasi via REST API **WAJIB** menyertakan meta key internal Yoast berikut pada payload `meta`:

1. `_yoast_wpseo_linkdex`: Wajib diisi dengan nilai string dari `seo_score` (contoh: `"85"`). Meta key ini yang mengontrol indikator bulatan **Hijau (>=80)**, Kuning, atau Merah di daftar artikel WP secara langsung tanpa perlu membuka editor WP secara manual.
2. `_yoast_wpseo_content_score`: Wajib diisi dengan nilai skor keterbacaan/readability (contoh: `"90"`).
3. `_yoast_wpseo_estimated_reading_time_minutes`: Wajib dihitung otomatis berdasarkan akumulasi kata konten dibagi 200.
4. `_yoast_wpseo_title`, `_yoast_wpseo_metadesc`, `_yoast_wpseo_focuskw`: Meta Yoast standar hasil input form.

---

## 4. Aturan Keamanan & Otomasi Penulis (Security & Dynamic Author)
1. **Atribusi Penulis Dinamis (Dynamic Author Resolution):**
   * Sistem dilarang menerbitkan artikel atas nama akun administrator generik.
   * Worker Queue wajib melakukan *lookup* `username` penulis dari dasbor ke REST API WordPress target. Jika tidak ditemukan, sistem diperbolehkan menggunakan ID Penulis *Fallback* yang dikonfigurasi pada situs terkait.
2. **Enkripsi Kredensial Sensitif:**
   * Seluruh `wp_app_password` situs target wajib disimpan dalam bentuk terenkripsi di basis data menggunakan fungsi `Crypt::encryptString()` dan didekripsi hanya saat job eksekusi berjalan.
3. **Pembatasan Peran Spatie & Tenant Scope:**
   * **Admin PIC Per Holding** hanya berhak mendaftarkan akun pengguna bertipe **Author** yang secara otomatis terikat pada `company_id` tempat PIC bertugas.
   * Kueri Eloquent untuk Admin PIC & Author **WAJIB** menerapkan Global/Local Scope `whereIn('company_id', $userHoldingIds)` untuk mencegah kebocoran data antar holding (*cross-tenant data leakage*).

---

## 5. Standar Kode & Arsitektur (Laravel Best Practices)
* **Framework & Versi:** Laravel 12.x (PHP 8.4+)
* **Pola Controller Ringkas (Thin Controller):** 
  * Controller DILARANG memuat logika kalkulasi SEO, panggilan REST API WordPress, ataupun aturan validasi manual.
  * Controller hanya bertugas memanggil `FormRequest` dan membalas respon dari `Service`.
* **Validasi Terisolasi (Dedicated Form Requests):**
  * Seluruh validasi input form (create/update article, user registration, wp site setup) WAJIB menggunakan kelas `FormRequest` tersendiri (contoh: `StoreArticleRequest`, `UpdateArticleRequest`).
* **Isolasi Logika Bisnis (Service Classes):**
  * Seluruh kalkulasi SEO dan manipulasi data artikel dimasukkan ke dalam `App\Services\ArticleService` dan `App\Services\SeoAnalyzerService`.
  * Seluruh komunikasi REST API WordPress dimasukkan ke dalam `App\Services\WordPressPublisherService`.
* **Pemrosesan Asinkron (Asynchronous Queue):**
  * Pengiriman artikel ke WordPress WAJIB dipicu dari Service melalui Job (`SendArticleToWordPressJob`) yang mengimplementasikan `ShouldQueue`.
