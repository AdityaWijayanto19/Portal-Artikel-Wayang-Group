# Panduan Antarmuka dan Desain (DESIGN.md)

## 1. Palet Warna Branding Wayang Group (Brand Identity)
Desain antarmuka dasbor mengadopsi identitas visual resmi Wayang Group dengan tema elegan, profesional, dan modern.

### A. Warna Utama Branding (Primary Colors)
* **Gold Accent (Emas Utama):** `#C59B27` / `#D4AF37`
  * *Penggunaan:* Tombol aksi utama (Primary Button), highlight status aktif, garis aksen header, dan elemen branding Wayang.
* **Dark Charcoal (Abu-Abu Gelap Kontras):** `#2D2D2D` / `#363636`
  * *Penggunaan:* Latar belakang sidebar, header dasbor, dan teks utama pada mode terang.
* **Warm Brown / Bronze:** `#6B4F2B` / `#8B5E34`
  * *Penggunaan:* Elemen batas (border), aksen pendukung, dan header kartu informasi.
* **Cream / Light Beige (Latar Belakang):** `#FDFBF7` / `#F5F0E6`
  * *Penggunaan:* Latar belakang halaman utama (workspace), area form input, dan latar kartu dasbor agar nyaman di mata.

---

## 2. Sistem Indikator Warna SEO Score (SEO Gatekeeper)
Warna indikator penilaian SEO mengikuti standar lampu lalu lintas (Traffic Light System) yang kontras di atas latar beige/charcoal:

* **0 – 59 | 🔴 Poor (Sangat Kurang):** `#E11D48` (Rose / Red)
  * *Visual:* Ikon silang merah `❌`, angka skor merah, tombol *Publish* **Muted / Disabled** (Warna `#9CA3AF`).
* **60 – 79 | 🟡 Needs Improvement (Perlu Perbaikan):** `#D97706` (Amber / Yellow)
  * *Visual:* Ikon peringatan `⚠️`, angka skor kuning-oranye, tombol *Publish* **Muted / Disabled**, tombol *Save Draft* aktif.
* **80 – 100 | 🟢 Good (Bagus):** `#059669` (Emerald / Green)
  * *Visual:* Ikon centang `✅`, angka skor hijau, tombol *Publish* **Aktif (Enabled)** menggunakan warna aksen Gold `#C59B27` atau Hijau `#059669`.

---

## 3. Komponen Antarmuka Editor & Widget SEO (Alpine.js)

### A. Tata Letak Form Editor (Two-Column Layout)
* **Kolom Kiri (Area Kerja Penulis - 70% Width):**
  * Input Judul Artikel, URL Slug, dan Metadata SEO (SEO Title, Meta Description, Focus Keyword).
  * WYSIWYG Editor (Konten Utama).
  * Pengunggah Media Gambar Utama + Input Alt Text Image.
  * Opsi Pemilihan Kategori (ex: SDPPI, SNI, TKDN).
* **Kolom Kanan (Sidebar Widget SEO & Actions - 30% Width):**
  * Widget Skor SEO Realtime (Kotak Melayang / Fixed Sidebar).
  * Tombol Aksi: `[Simpan Draf]` dan `[Publish ke WordPress]`.

### B. Widget SEO Score Realtime (Sidebar Component)
* **Header Skor:** Menampilkan badge skor besar berbasis warna indikator, contoh: `🟢 84 / 100` atau `🔴 53 / 100`.
* **State Tombol Publikasi:**
  * **Jika Skor >= 80:** Tombol `[Publish]` aktif dengan warna aksen Wayang Gold `#C59B27`, kursor dapat diklik.
  * **Jika Skor < 80:** Tombol `[Publish Disabled]` bergaya redup (*muted/opacity 50%*), `cursor: not-allowed`.
* **Panel Checklist & Rekomendasi Perbaikan:**
  * Menampilkan 12 item indikator SEO secara realtime.
  * Tanda `✅` untuk komponen yang lulus bobot.
  * Tanda `❌` beserta teks rekomendasi aksi (contoh: *"Meta description belum ada"*, *"Gambar belum memiliki Alt Text"*, *"Konten kurang dari 800 kata"*) ketika skor belum mencapai 80.

---

## 4. Single UI Codebase & Dynamic Role Rendering
Antarmuka dibangun dalam **1 folder UI utama** yang menyesuaikan tampilannya secara dinamis berdasarkan peran Spatie pengguna:

1. **Super Admin Team Website:**
   * Sidebar menampilkan seluruh menu: Holding, All Users, WP Sites Target, All Articles, dan Logs.
   * Dropdown filter holding aktif untuk memantau seluruh anak perusahaan.
2. **Admin PIC Per Holding:**
   * Sidebar menampilkan menu: Kelola Author Holding, WP Sites Target Holding, dan Daftar Artikel Holding.
   * Header menampilkan nama Holding tempat PIC bertugas.
3. **Author (Penulis):**
   * Sidebar ringkas: Halaman Dasbor Saya dan Buat/Kelola Artikel Saya.
   * Bebas dari menu konfigurasi sistem / WP Sites.
