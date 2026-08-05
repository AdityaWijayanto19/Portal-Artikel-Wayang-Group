# Dokumen Arsitektur Sistem

## 1. Spesifikasi Teknologi Utama
Sistem ini menggunakan arsitektur terpusat berbasis Laravel 12 dengan pemrosesan tugas latar belakang (asynchronous queue) untuk berkomunikasi ke WordPress REST API v2.

* **Backend Framework:** Laravel 12.x (PHP 8.4+)
* **Basis Data:** MySQL 8.0+ (InnoDB Engine)
* **Keamanan & RBAC:** Spatie Laravel-Permission & Custom Multi-Tenant Scope (`company_id`)
* **Mesin Evaluasi SEO:** Engine Evaluasi Realtime (Alpine.js) di Sisi Klien & Validasi Server-side (Laravel Service/Policy)
* **Integrasi API WordPress:** Laravel HTTP Client (Guzzle) via Basic Auth (`Application Passwords` Terenkripsi)
* **Pemrosesan Asinkron:** Laravel Queue (Database/Redis Driver) dikelola oleh Supervisor
* **Antarmuka Pengguna (UI):** Single UI Codebase (Tailwind CSS & Alpine.js) dengan Role-Based Rendering

---

## 2. Diagram Alur Data, Evaluasi SEO, & Publikasi

```text
[ Author / Admin PIC ]
        │
        ▼
[ Input Form Artikel di Dasbor ]
 (Judul, Konten, Slug, Kategori, Media + Alt, Meta SEO)
        │
        ▼
[ Mesin Evaluasi SEO Realtime (Alpine.js) ]
        │
        ├── Kalkulasi 12 Indikator SEO (Skala Skor 0 - 100)
        │
        ├── SKOR < 60 (🔴 Poor) ─────────► [ Disable Tombol Publish + Tampilkan Rekomendasi ]
        ├── SKOR 60-79 (🟡 Improvement) ──► [ Disable Publish + Enable Save Draft Only ]
        └── SKOR >= 80 (🟢 Good) ─────────► [ Enable Tombol Publish ]
                                                  │
                                                  ▼
                                    [ Klik Publish oleh Author ]
                                                  │
                                                  ▼
                                    [ Validasi Server-side Laravel ]
                                    (Cek Ulang Skor >= 80 & Tenant Scope)
                                                  │
                                                  ▼
                                    [ Push Job ke Laravel Queue Worker ]
                                                  │
                                ┌─────────────────┼─────────────────┐
                                ▼                 ▼                 ▼
                          [ Situs WP 1 ]    [ Situs WP 2 ]    [ Situs WP 3 ]
```

---

## 3. Keputusan Teknikal Utama (Architectural Decisions)

### A. Arsitektur Hak Akses & Multitenansi (Spatie + Company Scope)
* **Keputusan:** Menggabungkan Spatie Laravel-Permission untuk manajemen peran (`super_admin`, `admin_pic`, `author`) dengan tabel pivot `company_user` untuk pengisolasian data holding.
* **Pertimbangan & Keuntungan:**
  * Memungkinkan Admin PIC membuat akun **Author** khusus yang langsung otomatis terikat ke holding miliknya.
  * Mencegah terjadinya kebocoran akses data (*data leakage*) antar holding pada tingkat kueri ORM Eloquent.

### B. Single UI Codebase dengan Role-Based Dynamic Rendering
* **Keputusan:** Seluruh peran pengguna menggunakan 1 folder struktur tampilan (UI View) yang sama, bukan memisahkannya menjadi 3 folder UI terpisah.
* **Pertimbangan & Keuntungan:**
  * Memudahkan perawatan kode (*maintenance*) karena perubahan tata letak cukup dilakukan di satu tempat.
  * Tampilan elemen UI (menu sidebar, tombol publish, opsi holding) disesuaikan secara dinamis menggunakan *directive* Spatie (`@hasrole` / `@can`).

### C. Mesin Evaluasi SEO Ganda (Client-Side Realtime + Server-Side Guard)
* **Keputusan:** Kalkulasi skor SEO dijalankan secara *realtime* di antarmuka menggunakan Alpine.js, tetapi validasi batas skor minimal (>= 80) dihitung ulang di *controller/service* Laravel sebelum data dikirim ke queue.
* **Pertimbangan & Keuntungan:**
  * Memberikan umpan balik instan tanpa *lag* bagi penulis saat menyusun artikel.
  * Menjamin keamanan sistem dari peretasan/pemintasan permintaan API (*request bypass*) dari sisi klien.

### D. Resolusi Penulis Dinamis (Dynamic Author Resolution)
* **Keputusan:** Kredensial koneksi API menggunakan *Application Password* tingkat sistem, tetapi muatan postingan membawa ID penulis yang dicocokkan otomatis berdasarkan `username` pengguna dasbor di WordPress target.
* **Pertimbangan & Keuntungan:**
  * Atribusi penulis di WordPress target 100% akurat sesuai pembuat artikel di dasbor.
  * Penulis tidak perlu menyimpan atau memasukkan kata sandi akun WordPress pribadi mereka di dasbor terpusat.

---

## 4. Alur Kerja Sinkronisasi API WordPress (Sync Lifecycle)

1. **Penyusunan Konten (Drafting & Scoring):**
   * Penulis mengisi form artikel dan melengkapi seluruh parameter SEO hingga skor indikator mencapai 🟢 **>= 80**.
2. **Pemicuan Tugas (Job Dispatch):**
   * Setelah diklik *Publish*, controller memvalidasi hak akses dan skor SEO. Jika sah, status artikel diubah menjadi `queued` dan tugas dikirim ke Laravel Queue.
3. **Resolusi Penulis Dinamis (Author Resolution):**
   * Worker antrean melakukan pencarian akun di WordPress target berbasis `username`.
   * Jika username ditemukan, ID penulis tersebut dipasang pada *payload*. Jika tidak ditemukan, sistem menggunakan ID *fallback* default.
   * Worker memposting artikel ke endpoint `/wp/v2/posts` membawa Judul, Konten, Slug, Kategori, `featured_media`, serta Meta Keys Yoast SEO (`_yoast_wpseo_*`).
4. **Pengiriman Media, Artikel, & Meta Yoast SEO:**
   * Worker mengunggah Gambar Utama beserta *Alt Text* ke endpoint `/wp/v2/media` dan mengambil `media_id`.
   * Worker memposting artikel ke endpoint `/wp/v2/posts` membawa Judul, Konten, Slug, Kategori, `featured_media`.
   * **Injeksi Meta Yoast:** Worker menyertakan meta key internal Yoast (`_yoast_wpseo_linkdex` berisi nilai `seo_score`, `_yoast_wpseo_content_score`, dan `_yoast_wpseo_estimated_reading_time_minutes`) agar status warna indikator SEO di WordPress target langsung berubah menjadi **HIJAU** tanpa perlu membuka/mengedit ulang postingan di WordPress.
5. **Pencatatan Log Pengiriman (Logging & Retry):**
   * Hasil eksekusi API dicatat di tabel `article_wp_logs`.
   * Jika koneksi gagal, sistem menjalankan *auto-retry*. Jika tetap gagal, status diubah menjadi `failed` dan tombol pemicu ulang manual (*manual retry*) diaktifkan di dasbor.

---

## 5. Pola Arsitektur Kode (Layered Architecture & Best Practices)

Sistem menerapkan pemisahan tanggung jawab (*Separation of Concerns*) secara ketat untuk menjaga kebersihan kode (Clean Code) dan kemudahan pengujian (Maintainability):

* **Form Request Layer (`app/Http/Requests/`):**
  * Bertanggung jawab 100% atas aturan validasi data masuk (input sanitization & validation rules).
  * Controller tidak boleh memuat `$request->validate()` secara langsung.
* **Controller Layer (`app/Http/Controllers/`):**
  * Bertindak sebagai *Thin Controller* (penerima & pengarah lalu lintas).
  * Hanya menerima data dari Form Request, memanggil method di Service Layer, dan mengembalikan respon (JSON / View Render).
* **Service Layer (`app/Services/`):**
  * Tempat seluruh logika bisnis diisolasi (*Thick Services*).
  * Terdiri dari kelas spesifik seperti:
    * `SeoAnalyzerService`: Menghitung skor 12 indikator SEO dari konten.
    * `WordPressPublisherService`: Mengelola autentikasi API WP, resolusi username penulis, dan payload meta Yoast.
    * `ArticleService`: Mengelola penulisan artikel, perubahan status, dan dispatching Queue Job.
* **Data Access / Repository Layer (Eloquent Models):**
  * Mengelola relasi data, mutator/accessor, serta Multi-Tenant Global Scopes (`company_id`).
