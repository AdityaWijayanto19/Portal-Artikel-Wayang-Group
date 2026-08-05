# Dokumen Kebutuhan Produk (PRD)
**Nama Proyek:** Dasbor Admin Pengelolaan Artikel Terpusat Wayang Group  
**Penulis:** Aditya  
**Versi:** 1.0.0  
**Target Pelaksanaan:** Sprint MVP (Produk Layak Jalan)  

---

## 1. Latar Belakang dan Batasan Masalah
Wayang Group merupakan perusahaan induk yang membawahi beberapa holding / anak perusahaan, di mana setiap entitas mengoperasikan satu atau lebih situs web berbasis WordPress. 

Saat ini, pengelolaan artikel menghadapi tantangan operasional dan kualitas:
1. **Atribusi Penulis Dinamis (Dynamic Author Attribution):** Perlunya mekanisme pencocokan identitas penulis di dasbor ke akun WordPress target secara otomatis agar publikasi tidak tercatat atas satu akun generik/sistem.
2. **Kendali Kualitas SEO Terpusat (SEO Quality Control):** Belum adanya sistem penapis otomatis yang memastikan kualitas optimasi mesin pencari (SEO) pada setiap artikel sebelum diizinkan terbit ke WordPress target.
3. **Pemisahan Hak Akses Bertingkat (Hierarchical RBAC):** Perlunya struktur otorisasi yang jelas antara pengelola pusat, penanggung jawab holding, dan penulis konten.

---

## 2. Tujuan Proyek
- **Pengelolaan Artikel Terpusat:** Menulis dan mengunggah artikel ke puluhan situs web WordPress target secara otomatis dari satu dasbor berbasis Laravel.
- **Gerbang Penapis SEO Realtime (Realtime SEO Gatekeeper):** Mengharuskan artikel mencapai Skor SEO minimal 80 dari 100 agar tombol publikasi dapat diaktifkan.
- **Sistem Hak Akses Bertingkat Spatie (RBAC):** Mengatur kewenangan pengguna berdasarkan 3 peran utama (Super Admin Team Website, Admin PIC Per Holding, dan Author).
- **Pencocokan Penulis Dinamis:** Memetakan akun penulis dasbor ke ID penulis di WordPress target secara otomatis tanpa memaparkan kata sandi pribadi.

---

## 3. Ruang Lingkup Fitur dan Hak Akses

### A. Hierarki Peran dan Otorisasi (Spatie Permission)
1. **Super Admin Team Website:**
   * Akses penuh tanpa batasan ke seluruh holding, situs web WordPress, pengguna, dan artikel.
   * Mengelola pendaftaran Admin PIC Per Holding dan struktur holding group.
2. **Admin PIC Per Holding:**
   * Akses terbatas khusus pada holding yang ditugaskan kepadanya.
   * Berhak mengelola pendaftaran situs web WordPress di bawah holding-nya.
   * Berhak membuat dan mendaftarkan akun **Author** baru khusus di holding-nya.
   * Dapat melihat dan mengedit seluruh artikel di dalam holding-nya.
3. **Author (Penulis):**
   * Didaftarkan oleh Admin PIC Per Holding.
   * Berhak menyusun, mengedit, dan mempublikasikan artikel miliknya sendiri di bawah holding terkait.

### B. Alur Penulisan & Form Artikel
1. **Input Informasi Konten:** Judul Artikel, Isi Konten (Rich Text Editor), URL Slug, Kategori (contoh: SDPPI, SNI, TKDN).
2. **Pengunggahan Media:** Gambar Utama (Featured Image) beserta Teks Alt Gambar (Alt Text Image).
3. **Pengisian Metadata SEO:** Judul SEO, Deskripsi Meta, dan Kata Kunci Fokus (Focus Keyword).

### C. Mesin Evaluasi SEO Realtime & Aturan Publikasi
- **Kalkulasi Skor Otomatis (Skala 0 - 100):** Menghitung 12 indikator SEO secara realtime saat artikel ditulis.
- **Kategori Skor Visual:**
  - `0 - 59`: 🔴 Poor (Sangat Kurang)
  - `60 - 79`: 🟡 Needs Improvement (Perlu Perbaikan)
  - `80 - 100`: 🟢 Good (Bagus)
- **Aturan Gerbang Publikasi (Publish Gatekeeper):**
  - **Skor >= 80:** Tombol `[Publish]` **Aktif (Enabled)**.
  - **Skor 60 - 79:** Tombol `[Publish]` **Muted/Disabled**, sistem hanya mengizinkan `[Save Draft]`.
  - **Skor < 60:** Tombol `[Publish]` **Muted/Disabled**, menampilkan panel rekomendasi hal-hal yang wajib diperbaiki.

### D. Integrasi API WordPress Latar Belakang (Laravel Queue)
- Pengiriman data artikel ke WordPress target diproses secara asinkron (background job).
- Pengiriman mencakup Judul, Konten, Kategori, Featured Image + Alt Text, Meta Keys Yoast SEO, serta Atribusi ID Penulis Dinamis.
- Pencatatan log status pengiriman (berhasil / gagal) beserta fitur pemicu ulang manual (*manual retry*).

---

## 4. Persyaratan Teknis Utama
- **Backend Framework:** Laravel 12.x (PHP 8.4+)
- **Basis Data:** MySQL 8.0+
- **Manajemen Hak Akses:** Spatie Laravel-Permission & Multi-Tenant Company Scope
- **Antarmuka Pengguna:** Tailwind CSS & Alpine.js (Live SEO Engine)
- **Integrasi Target:** WordPress REST API v2 (Application Passwords Encryption)

---

## 5. Indikator Keberhasilan Proyek
1. **Penjaminan Standar Mutu Konten 100%:** Tidak ada artikel yang terbit di WordPress target dengan Skor SEO di bawah 80.
2. **Akurasi Hak Akses 0% Kebocoran:** Admin PIC dan Author dari Holding A tidak dapat melihat atau memodifikasi data milik Holding B.
3. **Kecepatan Pengalaman Pengguna:** Proses pengiriman artikel ke banyak situs web selesai di bawah 2 detik pada antarmuka (diproses di latar belakang via Queue).
4. **Presisi Atribusi Penulis:** 100% artikel yang terbit di WordPress mencantumkan nama akun penulis yang sesuai.
