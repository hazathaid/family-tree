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

Ketentuan production:

- Konfigurasi backup wajib menulis salinan terenkripsi ke object storage yang durable dan terpisah dari host aplikasi. Disk lokal hanya boleh menjadi staging sementara, bukan satu-satunya salinan.
- Password enkripsi arsip, kredensial storage, dan kredensial database hanya boleh berasal dari secret manager atau mekanisme secret deployment. Nilainya tidak boleh dicatat dalam repository, image container, atau log.
- Owner operasional wajib menetapkan retensi, RPO/RTO, penerima `backup:monitor`, dan jalur eskalasi ketika backup gagal atau ukuran backup tidak wajar.
- Akses untuk membuat, membaca, dan memulihkan backup mengikuti least privilege. Operator restore menggunakan akun database khusus yang dibatasi pada database target.

Prosedur restore production:

1. Pilih backup sehat dari `php artisan backup:list`, salin ke lokasi terisolasi, lalu verifikasi arsip dapat dibuka.
2. Aktifkan maintenance mode: `php artisan down --retry=60` dan hentikan Horizon dengan `php artisan horizon:terminate`.
3. Buat backup database saat ini sebelum restore.
4. Ekstrak dump database dari ZIP. Jangan mengekstrak `.env` atau konfigurasi dari sumber yang tidak dipercaya.
5. Restore dengan client database menggunakan kredensial dari secret manager, misalnya `mysql --single-transaction DATABASE < database.sql`.
6. Jalankan `php artisan migrate --force`, `php artisan optimize:clear`, lalu validasi `GET /api/v1/health`.
7. Mulai kembali Horizon dan scheduler, kemudian `php artisan up`.

Restore bersifat destruktif sehingga sengaja tidak tersedia sebagai endpoint HTTP atau command tanpa konfirmasi operator. Operator harus memastikan client dump yang kompatibel tersedia sebelum maintenance dimulai dan mencatat backup sumber, waktu restore, hasil validasi health check, serta otorisasi perubahan pada incident/change record.

Latihan restore dilakukan secara berkala pada lingkungan terisolasi dengan dump representatif. Bukti latihan harus mencatat durasi aktual, integritas data hasil restore, serta tindakan perbaikan bila target RTO/RPO tidak terpenuhi.

## Monitoring

- `SystemHealthService` memeriksa konektivitas database dan Redis melalui repository.
- Horizon menangani worker Redis dan menyimpan snapshot setiap lima menit.
- Telescope aktif secara default di non-production; production harus mengaktifkannya eksplisit dengan `TELESCOPE_ENABLED=true`.
- Sentry aktif ketika `SENTRY_LARAVEL_DSN` tersedia. Trace sampling dikontrol dengan `SENTRY_TRACES_SAMPLE_RATE`.
- Container `scheduler` menjalankan jadwal backup dan maintenance.
- Kegagalan `backup:monitor`, kegagalan scheduler, dan anomali ukuran/usia backup harus mengirim alert ke on-call yang ditetapkan. Keberhasilan backup bukan pengganti verifikasi restore berkala.
