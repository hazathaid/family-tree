# Phase 16 Services and Operations

## Security

- Semua API memakai rate limit global; login memiliki limit khusus.
- Security headers mencakup CSP, anti-framing, MIME sniffing protection, referrer policy, permissions policy, serta HSTS untuk HTTPS.
- Web routes tetap memakai CSRF middleware bawaan Laravel. API memakai token Sanctum.
- Konten rich text tetap diproses `RichTextSanitizer`.
- Horizon dan Telescope hanya dapat dibuka oleh user dengan role `super-admin`.
- Telescope menyembunyikan token, password, cookie, CSRF header, dan authorization header.

## Backup dan Restore

Backup manual:

```bash
php artisan backup:run --only-db
php artisan backup:list
php artisan backup:monitor
```

Prosedur restore production:

1. Pilih backup sehat dari `php artisan backup:list`, salin ke lokasi terisolasi, lalu verifikasi arsip dapat dibuka.
2. Aktifkan maintenance mode: `php artisan down --retry=60` dan hentikan Horizon dengan `php artisan horizon:terminate`.
3. Buat backup database saat ini sebelum restore.
4. Ekstrak dump database dari ZIP. Jangan mengekstrak `.env` atau konfigurasi dari sumber yang tidak dipercaya.
5. Restore dengan client database menggunakan kredensial dari secret manager, misalnya `mysql --single-transaction DATABASE < database.sql`.
6. Jalankan `php artisan migrate --force`, `php artisan optimize:clear`, lalu validasi `GET /api/v1/health`.
7. Mulai kembali Horizon dan scheduler, kemudian `php artisan up`.

Restore bersifat destruktif sehingga sengaja tidak tersedia sebagai endpoint HTTP atau command tanpa konfirmasi operator.

## Monitoring

- `SystemHealthService` memeriksa konektivitas database dan Redis melalui repository.
- Horizon menangani worker Redis dan menyimpan snapshot setiap lima menit.
- Telescope aktif secara default di non-production; production harus mengaktifkannya eksplisit dengan `TELESCOPE_ENABLED=true`.
- Sentry aktif ketika `SENTRY_LARAVEL_DSN` tersedia. Trace sampling dikontrol dengan `SENTRY_TRACES_SAMPLE_RATE`.
- Container `scheduler` menjalankan jadwal backup dan maintenance.
