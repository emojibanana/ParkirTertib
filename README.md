# ParkirTertib — Sistem Pelaporan Parkir Partisipatif UNIMED

Aplikasi web untuk melaporkan pelanggaran parkir motor di Universitas Negeri Medan (UNIMED).
Mahasiswa melapor via HP dengan foto bukti, Satpam menindaklanjuti via dashboard desktop.

**Live demo:** https://parkirtertib.freehosting.dev/

## Akun Demo (klik link di atas untuk mencoba)

| Peran | Email | Password |
|---|---|---|
| Satpam (admin) | `satpam@unimed.ac.id` | `Password123!` |
| Mahasiswa (user) | `ahmad@mhs.unimed.ac.id` | `Password123!` |

Alur coba: daftar akun baru → buat laporan + foto → login satpam → ubah status
(`Menunggu` → `Diproses` → `Ditindaklanjuti` → `Selesai`) → cek halaman pelanggar berulang.

## Fitur Utama

- **Autentikasi 2 peran** (`user` / `admin`), session aman + CSRF di semua form.
- **Lapor pelanggaran** dengan foto (JPG/PNG/WebP ≤ 5MB, MIME diverifikasi), pelat
  otomatis dinormalisasi (`BK 1234 ABC` → `BK1234ABC`).
- **Batas 3 laporan aktif** per user anti-spam.
- **Riwayat laporan** mobile-first: badge status, catatan Satpam, tanggal Indonesia.
- **Dashboard Satpam**: kartu KPI, pencarian pelat/lokasi, filter status, timeline audit.
- **Pelanggar berulang**: agregasi per pelat, terurut pelanggaran terbanyak.
- **Cleanup otomatis**: foto laporan `Selesai` > 7 hari dihapus (tombol dashboard / CLI
  `php bin/cleanup_photos.php`).

## Tech Stack

PHP 8.2+ Native (OOP, PDO) · MySQL 8 / MariaDB · HTML + Tailwind CSS v3 + Vanilla JS ·
PHPUnit 12 · PHPStan level 5 · PHPCS PSR-12. Tanpa framework, pola MVC + Front Controller.

## Instalasi Lokal

```bash
# 1. Dependensi
composer install

# 2. Database ( Laragon/XAMPP sudah jalan )
mysql -u root < schema.sql

# 3. Konfigurasi (opsional, default sudah cocok untuk Laragon)
copy .env.example .env

# 4. Jalan (pilih salah satu)
php -S localhost:8000 -t public/
# atau via Laragon: http://parkir.test (DocumentRoot ke /public)

# 5. Database uji untuk integration test
mysql -u root -e "CREATE DATABASE parkir_tertib_test"
(Get-Content schema.sql -Raw) -replace 'parkir_tertib','parkir_tertib_test' | mysql -u root
```

Build ulang CSS setelah mengubah view:

```bash
./tailwindcss -i public/assets/css/app.css -o public/assets/css/style.css --minify
```

## Testing & Quality

```bash
vendor/bin/phpunit                        # 54 tests, 175 assertions
vendor/bin/phpstan analyse src/ --level=5 # static analysis
vendor/bin/phpcs --standard=PSR12 src/    # code style
php bin/cleanup_photos.php                # dry-run cleanup foto
```

## Struktur Proyek

```
public/         # Document root: index.php, assets, uploads
src/            # Controllers, Models, Middlewares, Helpers (App\*)
views/          # Template PHP: auth, user, admin, layouts, errors
config/         # env loader + koneksi PDO
bin/            # cleanup_photos.php (CLI)
tests/          # Unit + Integration (DB parkir_tertib_test)
schema.sql      # DDL + seed, schema_hosting.sql versi shared hosting
```

## Keamanan

Prepared statements (anti SQLi) · `htmlspecialchars` di semua view (anti XSS) ·
`password_hash` Bcrypt · `session_regenerate_id` + cookie `HttpOnly`/`SameSite=Lax` ·
token CSRF · validasi MIME via `finfo_file` + nama file acak · eksekusi PHP dimatikan
di `public/uploads/` · proteksi `.env` dan folder sumber via `.htaccess`.

## Lisensi

Proyek akademik — bebas dipakai untuk pembelajaran.
