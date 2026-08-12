# Panduan Deploy & Maintenance — Hostinger

> Project: Automation Artikel Wayang Group (Laravel 12, multi-tenant, publish artikel ke WordPress via queue)
> Server: Hostinger shared hosting — subdomain **artikel.wayang.group** (numpang di server wayang.group)

---

## 1. Informasi Dasar Server

| Item | Nilai |
|---|---|
| Hosting | Hostinger shared hosting (CloudLinux 8) |
| SSH | `ssh -p 65002 u711185757@193.203.172.201` |
| Domain | `wayang.group` |
| Subdomain app | `artikel.wayang.group` |
| Path project (SSH) | `/home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang` |
| Docroot subdomain | = folder project (root), isi `public/` diarahkan lewat `.htaccess` |
| PHP versi | **8.4** (binary: `/opt/alt/php84/usr/bin/php`) |
| Composer | `/usr/local/bin/composer` (harus dipanggil lewat PHP 8.4, lihat §2) |
| Database | MySQL (hPanel) — driver session/cache/queue = database |

**Aturan penting:** SEMUA perintah `artisan`/`composer` di server WAJIB pakai binary PHP 8.4:

```bash
/opt/alt/php84/usr/bin/php artisan ...
/opt/alt/php84/usr/bin/php /usr/local/bin/composer ...
```

---

## 2. Setup Komposer (PHP CLI) di Server

`.bashrc` user SSH sudah punya alias, tapi **jangan pernah hapus baris export PATH PHP 8.4**. Alias composer yang benar:

```bash
alias composer='/opt/alt/php84/usr/bin/php /usr/local/bin/composer'
```

> Peringatan: alias lama yang rekursif (`$(which composer)`) akan bikin error `Could not open input file: alias`. Kalau composer error, cek `grep composer ~/.bashrc`.

Install dependency (sudah pernah dijalankan, untuk reference):

```bash
cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang
/opt/alt/php84/usr/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

---

## 3. Ekstensi PHP yang WAJIB Aktif

Cek dengan:

```bash
/opt/alt/php84/usr/bin/php -m
/opt/alt/php84/usr/bin/php artisan about
```

| Ekstensi | Fungsi | Status |
|---|---|---|
| `pdo_mysql` | Koneksi MySQL | WAJIB |
| `mbstring` | String multibyte | WAJIB |
| `openssl` | Security/encryption | WAJIB |
| `tokenizer` | Laravel core | WAJIB |
| `xml`, `dom`, `ctype`, `json` | Laravel core | WAJIB |
| `curl` | **HTTP client ke REST API WordPress (publish artikel)** | WAJIB |
| `fileinfo` | Deteksi mime upload | WAJIB |
| `gd` ATAU `imagick` | **Proses gambar (Intervention Image)** | WAJIB (salah satu) |
| `exif` | Metadata gambar (intervention) | Disarankan |
| `intl` | Lokalisasi | Disarankan |
| `bcmath` | Utilitas numerik | Disarankan |

Kalau ada yang kurang: aktifkan lewat **hPanel → Websites → PHP Configuration → Extension**, atau hubungi support Hostinger.

---

## 4. Frontend (Vite + Tailwind + Alpine.js)

**PENTING:** Alpine.js TIDAK lagi dimuat dari CDN unpkg (sering keblokir di Hostinger). Sudah di-bundle lewat Vite:

- `resources/js/app.js` → `import Alpine from 'alpinejs'` + `Alpine.plugin(collapse)`
- CDN `unpkg.com` di `app.blade.php` sudah dihapus
- Dependency: `alpinejs` + `@alpinejs/collapse` (sudah di `package.json`)

Folder **`public/build` ada di `.gitignore`** → hasil build TIDAK ikut git. Wajib upload manual via SFTP.

**Prosedur update frontend (dari Laragon):**

```bash
npm install
npm run build
```

Lalu:
1. `git add -A && git commit && git push` (kode ikut git)
2. Upload folder `public/build` lokal → server via SFTP (overwrite) ke:
   `/home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang/public/build`
3. Di server (SSH):

```bash
cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang
git pull
/opt/alt/php84/usr/bin/php artisan view:clear config:cache route:cache view:cache
```

> JANGAN `npm install`/`npm run build` di server shared hosting — Node.js tidak tersedia di default SSH. Build selalu dari Laragon.

---

## 5. Struktur Docroot (jangan diubah sembarangan)

- Root project = docroot subdomain.
- `.htaccess` di ROOT project berisi rewrite ke `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

- JANGAN pindahkan isi `public/` ke atas — bikin ribet saat `git pull`.
- File `public/hot` = penanda Vite dev, **wajib dihapus** di production (`rm -f public/hot`).

---

## 6. QUEUE — KUNCI (publish artikel ke WordPress)

Aplikasi mengirim artikel ke WordPress lewat job queue (`database` driver). Shared hosting tidak bisa daemon `queue:work`, jadi pakai **Cron Job**.

> **PENTING — yang sudah terbukti jalan:** cron hPanel di Hostinger **merusak** command yang mengandung `>` atau `$(...)` (file jadi dibuat kosong, output tidak muncul). Solusi: **selalu lewat file script `.sh`** — command cron cuma 1 token, tanpa karakter rumit.

### a. Buat script runner (sekali saja)

```bash
cat > /home/u711185757/queue_runner.sh <<'EOF'
#!/bin/bash
echo "[$(date)] queue runner jalan" >> /home/u711185757/queue_worker.log
cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang
/opt/alt/php84/usr/bin/php artisan queue:work --stop-when-empty --timeout=600 --max-time=300 >> /home/u711185757/queue_worker.log 2>&1
EOF
chmod +x /home/u711185757/queue_runner.sh
```

### b. Pasang cron di hPanel

**hPanel → Advanced → Cron Jobs → tambahkan:**

- Schedule: `* * * * *` (every minute)
- Command (hanya ini, tanpa `cd`/`&&`):
  ```
  /home/u711185757/queue_runner.sh
  ```

### c. Verifikasi

```bash
cat /home/u711185757/queue_worker.log
```

Baris `[tanggal] queue runner jalan` muncul tiap menit → cron bekerja.

> Tanpa cron ini, tombol "Publish" jalan tapi artikel TIDAK PERNAH terkirim ke WordPress.

### d. Cek antrian & job gagal

```bash
cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang
/opt/alt/php84/usr/bin/php artisan queue:monitor default
/opt/alt/php84/usr/bin/php artisan queue:failed
/opt/alt/php84/usr/bin/php artisan queue:flush
```

---

## 7. Prosedur Deploy Awal (reference, sudah dilakukan)

1. Clone project ke folder docroot
2. `composer install --no-dev --optimize-autoloader` (pakai PHP 8.4)
3. `cp .env.example .env`
4. Isi `.env`:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://artikel.wayang.group

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_DATABASE=<nama_db>
   DB_USERNAME=<user_db>
   DB_PASSWORD=<pass_db>
   ```
5. `php artisan key:generate`

   > **KRUSIAL jika membawa DB lokal ke production:** JANGAN pakai `key:generate` — salin `APP_KEY` dari `.env` lokal (Laragon). Semua field `encrypted` (mis. `wp_app_password` di WPSite) ter-ikat ke key; key beda = error `The MAC is invalid.` / `DecryptException`.
   >
   > ```bash
   > sed -i 's|^APP_KEY=.*|APP_KEY=base64:<KEY-DARI-LOKAL>|' .env
   > /opt/alt/php84/usr/bin/php artisan config:clear && /opt/alt/php84/usr/bin/php artisan config:cache
   > ```
   > (Selama debugging, jangan `config:cache` dulu — cache mengunci key lama & mengabaikan perubahan `.env`.)

6. Import DB: import dump `.sql` lokal via phpMyAdmin hPanel, ATAU `php artisan migrate --seed --force`
7. Buat `.htaccess` rewrite (§5)
8. `php artisan storage:link` + `chmod -R 775 storage bootstrap/cache`
9. Upload `public/build` (§4)
10. Buat cron queue (§6)
11. Aktifkan SSL untuk subdomain di hPanel
12. Verifikasi: buka `https://artikel.wayang.group/up` → harus `200 OK`

---

## 8. Update App (rutin, tiap mau deploy perubahan)

```bash
# Lokal (Laragon)
npm run build              # kalau ada perubahan frontend
git add -A && git commit -m "..." && git push

# Upload public/build via SFTP jika frontend berubah

# Server (SSH)
cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang
git pull
/opt/alt/php84/usr/bin/php artisan migrate --force        # kalau ada migration baru
/opt/alt/php84/usr/bin/php artisan view:clear config:cache route:cache view:cache
```

---

## 9. Troubleshooting Cepat

| Gejala | Penyebab / Solusi |
|---|---|
| `Could not open input file: alias` | Alias composer rekursif di `.bashrc` — perbaiki (§2) |
| `Your lock file does not contain a compatible set...` | PHP CLI masih 8.2 — gunakan `/opt/alt/php84/usr/bin/php` |
| `Could not resolve host` saat buka subdomain | DNS subdomain belum dibuat/SSL belum terpasang — cek hPanel |
| JS/CSS tidak termuat / tampilan polos | `public/build` belum di-upload / file `hot` masih ada |
| Sidebar/dropdown Alpine tidak jalan | Build lama (tanpa Alpine) — ikuti §4 |
| Artikel klik publish tapi tidak terkirim | Cron queue belum jalan (§6) atau ekstensi `curl` mati |
| Error gambar/gd | Ekstensi `gd`/`imagick` belum aktif (§3) |
| `Class "App\Models\WpSite" not found` (atau Class not found lain) | Beda besar/kecil nama file model. Windows case-insensitive, Linux tidak. Cek `app/Models/*.php` — class `WPSite` direferensikan sebagai `WpSite` di `Company.php:45` (sudah di-fix) |
| `DecryptException: The MAC is invalid` | APP_KEY server ≠ key yang meng-encrypt data. Salin `APP_KEY` lokal ke server (§7 poin 5) |
| `ENV_KEY=` kosong padahal `.env` sudah diisi | Config sedang di-cache (`config:cache`) — key lama terkunci. Jalankan `php artisan config:clear`, lalu cek `env('APP_KEY')` |
| Cron hPanel tidak jalan (`Lihat Output` kosong) | Jangan pakai command dengan `>`/`$(...)` di cron hPanel (diubah jadi kosong). Gunakan file script `.sh`, command cron = path script saja (§6) |
| `crontab -e` → `no crontab for u711185757` dan tidak bisa di-set | SSH crontab dibatasi di akun Hostinger ini — gunakan hPanel → Advanced → Cron Jobs |
| Cron jalan tapi file log kosong (file dibuat, isi kosong) | Shell cron hPanel tidak mendukung syntax tertentu — lewat script `.sh` saja |

---

## 10. Catatan Aplikasi

- Multi-tenant: data per company, pindah company via switcher di dashboard.
- Roles (Spatie): `super_admin`, `admin`, `author` (hasil seeder).
- Tiap WP Site butuh **Application Password** (REST API) — diatur lewat menu **WP Sites** di app.
- Session, cache, queue semuanya di database → tidak butuh Redis.
- Wajib HTTPS: `APP_URL` sudah https, SSL aktif di hPanel.
