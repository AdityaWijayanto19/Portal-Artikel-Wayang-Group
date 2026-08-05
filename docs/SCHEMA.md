# Struktur Basis Data dan Multitenansi (SCHEMA.md)

## 1. Spesifikasi Teknis Basis Data
* **DBMS:** MySQL 8.0+
* **Engine:** InnoDB
* **Collation:** `utf8mb4_unicode_ci`
* **Framework:** Laravel 12.x ORM Eloquent

---

## 2. Struktur Tabel Utama

### A. Tabel: `companies` (Holding / Anak Perusahaan)
* `id` (BIGINT, PK, Auto Increment)
* `name` (VARCHAR 255)
* `slug` (VARCHAR 255, Unique)
* `created_at` (TIMESTAMP, Nullable)
* `updated_at` (TIMESTAMP, Nullable)

### B. Tabel: `users` (Pengguna Dasbor)
* `id` (BIGINT, PK, Auto Increment)
* `name` (VARCHAR 255)
* `email` (VARCHAR 255, Unique)
* `username` (VARCHAR 255, Unique) -> *Dynamic Author Resolution*
* `password` (VARCHAR 255)
* `remember_token` (VARCHAR 100, Nullable)
* `created_at` (TIMESTAMP, Nullable)
* `updated_at` (TIMESTAMP, Nullable)

### C. Tabel Pivot: `company_user` (Multitenant User & Holding)
* `user_id` (BIGINT, FK -> `users.id` ON DELETE CASCADE)
* `company_id` (BIGINT, FK -> `companies.id` ON DELETE CASCADE)
* **Primary Key Gabungan:** (`user_id`, `company_id`)

### D. Tabel: `categories` (Kategori Artikel per Holding)
* `id` (BIGINT, PK, Auto Increment)
* `company_id` (BIGINT, FK -> `companies.id` ON DELETE CASCADE)
* `name` (VARCHAR 255)
* `slug` (VARCHAR 255)
* `created_at` (TIMESTAMP, Nullable)
* `updated_at` (TIMESTAMP, Nullable)

### F. Tabel: `wp_sites` (Situs Web WordPress Target)
* `id` (BIGINT, PK, Auto Increment)
* `company_id` (BIGINT, FK -> `companies.id` ON DELETE CASCADE)
* `site_name` (VARCHAR 255)
* `site_url` (VARCHAR 255)
* `wp_username` (VARCHAR 255)
* `wp_app_password` (TEXT) -> *Crypt::encryptString*
* `created_at` (TIMESTAMP, Nullable)
* `updated_at` (TIMESTAMP, Nullable)

### G. Tabel: `articles` (Data Artikel & Skor SEO)
* `id` (BIGINT, PK, Auto Increment)
* `company_id` (BIGINT, FK -> `companies.id` ON DELETE CASCADE)
* `user_id` (BIGINT, FK -> `users.id` ON DELETE CASCADE)
* `wp_site_id` (BIGINT, FK -> `wp_sites.id` ON DELETE CASCADE)
* `category_id` (BIGINT, FK -> `categories.id` ON DELETE RESTRICT)
* `title` (VARCHAR 255)
* `slug` (VARCHAR 255)
* `content` (LONGTEXT)
* `featured_image_path` (VARCHAR 255, Nullable)
* `image_alt_text` (VARCHAR 255, Nullable)
* `seo_score` (INT, Default: 0) -> *Skor Hasil Kalkulasi Engine (0 - 100)*
* `yoast_title` (VARCHAR 255, Nullable)
* `yoast_metadesc` (TEXT, Nullable)
* `yoast_focuskw` (VARCHAR 255, Nullable)
* `status` (ENUM: `'draft'`, `'queued'`, `'published'`, `'failed'`, Default: `'draft'`)
* `created_at` (TIMESTAMP, Nullable)
* `updated_at` (TIMESTAMP, Nullable)

### H. Tabel: `article_wp_logs` (Log Pengiriman REST API)
* `id` (BIGINT, PK, Auto Increment)
* `article_id` (BIGINT, FK -> `articles.id` ON DELETE CASCADE)
* `wp_site_id` (BIGINT, FK -> `wp_sites.id` ON DELETE CASCADE)
* `wp_post_id` (BIGINT, Nullable)
* `status` (ENUM: `'success'`, `'failed'`)
* `response_message` (TEXT, Nullable)
* `synced_at` (TIMESTAMP, Nullable)

---

## 3. Tabel Bawaan Spatie Laravel-Permission
* `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.
