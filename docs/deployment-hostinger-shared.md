# Deployment Demo ke Hostinger Shared Hosting

Panduan ini untuk **demo terbatas kepada investor**, bukan deployment produksi.
Gunakan Hostinger Web Premium atau Web Business yang menyediakan SSH, Composer
2, cron job, dan database MySQL.

Shared hosting tidak menyediakan akses root atau process manager. Karena itu:

- Redis dan Laravel Horizon tidak digunakan.
- cache, session, dan queue disimpan di MySQL.
- queue worker dijalankan berkala lewat cron dan tidak real-time.
- proses berat, export besar, dan pengujian 100.000 anggota tidak dijamin.
- Telescope dimatikan.

Contoh dalam panduan:

- domain demo: `demo.example.com`
- username Hostinger: `u123456789`
- root domain: `/home/u123456789/domains/demo.example.com`
- source Laravel: `/home/u123456789/domains/demo.example.com/app`
- web root: `/home/u123456789/domains/demo.example.com/public_html`

Gunakan `pwd` melalui SSH untuk mendapatkan path sebenarnya. Jangan menyalin
nilai contoh apa adanya.

## 1. Siapkan website demo

Di hPanel:

1. Buka **Websites → Add website**.
2. Pilih website PHP kosong dan hubungkan domain atau subdomain demo.
3. Buka **Websites → Dashboard → PHP Configuration**.
4. Pilih PHP `8.3`.
5. Di bagian PHP Extensions, pastikan `bcmath`, `curl`, `dom`, `fileinfo`, `gd`,
   `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `xml`, dan `zip` aktif.
6. Aktifkan SSL dan paksa HTTPS.
7. Aktifkan SSH dari **Advanced → SSH Access**.

Gunakan subdomain khusus demo agar tidak mengganggu website utama. DNS dapat
memerlukan waktu hingga 24 jam untuk propagasi.

## 2. Build dan test sebelum upload

Jalankan di komputer pengembang:

```bash
composer test
composer analyse
composer pint -- --test
npm ci
npm run build
```

Pastikan `public/build/manifest.json` terbentuk. Folder `public/build` tidak
masuk Git, sehingga hasil build harus diunggah saat deployment.

Shared hosting mempunyai batas CPU dan memori. Sebaiknya dependency PHP
dibangun di komputer atau CI yang menggunakan PHP 8.3:

```bash
composer install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader
```

Upload folder `vendor` bersama source jika `composer2 install` di server gagal
karena batas memori atau extension `pcntl` milik Horizon tidak tersedia.

## 3. Buat database dari hPanel

Buka **Websites → Dashboard → Databases → Management**, lalu buat:

- nama database
- username database
- password acak yang kuat

Catat hostname database yang ditampilkan hPanel. Pada sebagian akun nilainya
`localhost`, tetapi gunakan nilai yang benar dari dashboard.

## 4. Upload source di luar `public_html`

Struktur yang aman:

```text
domains/demo.example.com/
├── app/          # seluruh source Laravel dan .env
└── public_html/  # hanya isi folder public/
```

Jangan menaruh `.env`, `vendor`, `app`, `config`, atau source Laravel lain di
`public_html`.

### Pilihan A — Git lewat SSH

Masuk menggunakan perintah SSH dari hPanel:

```bash
ssh -p PORT_SSH u123456789@HOST_SSH
cd /home/u123456789/domains/demo.example.com
git clone URL_REPOSITORY app
cd app
git checkout main
```

Untuk repository private, gunakan deploy key atau personal access token dengan
hak baca minimum.

### Pilihan B — File Manager/SFTP

Arsipkan source tanpa `.env`, `node_modules`, log, dan file development yang
tidak dibutuhkan. Upload dan extract ke folder `app`, bukan `public_html`.

Jangan mengunggah `.env` dari komputer lokal karena mungkin berisi credential
development.

## 5. Instal dependency di server

Hostinger menyediakan Composer 2 pada Web Premium, Web Business, dan Cloud:

```bash
cd /home/u123456789/domains/demo.example.com/app
composer2 install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader \
  --ignore-platform-req=ext-pcntl
```

`ext-pcntl` hanya dibutuhkan Horizon, sedangkan Horizon tidak dijalankan pada
shared hosting. Jangan memakai `composer update` saat deployment karena dapat
mengubah versi dependency dari lock file.

Jika versi PHP CLI berbeda dari PHP website, gunakan binary PHP 8.3:

```bash
/opt/alt/php83/usr/bin/php /usr/local/bin/composer2 install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader \
  --ignore-platform-req=ext-pcntl
```

Jika Composer terkena memory limit, upload folder `vendor` hasil build lokal.

## 6. Buat `.env` demo

```bash
cd /home/u123456789/domains/demo.example.com/app
cp .env.example .env
chmod 600 .env
```

Edit `.env`:

```dotenv
APP_NAME="Family Tree Demo"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://demo.example.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=HOST_DATABASE_DARI_HPANEL
DB_PORT=3306
DB_DATABASE=NAMA_DATABASE_DARI_HPANEL
DB_USERNAME=USER_DATABASE_DARI_HPANEL
DB_PASSWORD="PASSWORD_DATABASE_DARI_HPANEL"

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public
TELESCOPE_ENABLED=false
BACKUP_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=HOST_SMTP
MAIL_PORT=587
MAIL_USERNAME=USERNAME_SMTP
MAIL_PASSWORD="PASSWORD_SMTP"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=demo@example.com
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=demo.example.com
```

Kosongkan atau hapus konfigurasi Redis dari `.env`. Untuk demo yang tidak
memerlukan email sungguhan, `MAIL_MAILER=log` dapat digunakan.

Buat application key:

```bash
/opt/alt/php83/usr/bin/php artisan key:generate
```

## 7. Publikasikan folder `public`

Salin isi `app/public` ke `public_html` melalui File Manager atau SSH:

```bash
cp -a /home/u123456789/domains/demo.example.com/app/public/. \
  /home/u123456789/domains/demo.example.com/public_html/
```

Pastikan hasil build lokal ikut disalin ke:

```text
public_html/build/manifest.json
```

Ganti isi `public_html/index.php` agar menunjuk ke source di folder `app`:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../app/vendor/autoload.php';

$app = require_once __DIR__.'/../app/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

Buat `public_html/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

Tambahkan perlindungan tambahan ke bagian paling atas `.htaccess`:

```apache
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

## 8. Atur permission dan storage foto

```bash
cd /home/u123456789/domains/demo.example.com/app
chmod -R u+rwX storage bootstrap/cache
```

Laravel membutuhkan URL `/storage` untuk file pada public disk. Aktifkan fungsi
symlink di hPanel jika dinonaktifkan, lalu:

```bash
ln -s \
  /home/u123456789/domains/demo.example.com/app/storage/app/public \
  /home/u123456789/domains/demo.example.com/public_html/storage
```

Jika `public_html/storage` sudah ada, periksa isinya terlebih dahulu dan jangan
menimpa folder tersebut secara paksa.

## 9. Migrasi dan data demo

```bash
cd /home/u123456789/domains/demo.example.com/app
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize
```

Jangan jalankan `DatabaseSeeder` pada demo yang dapat diakses publik tanpa
memeriksa isi seedernya.

Jika ingin memakai data Bani Fahruroji, ikuti
[`seeding-bani-fahruroji.md`](seeding-bani-fahruroji.md) dan tentukan owner
aktif:

```dotenv
BANI_FAHRUROJI_OWNER_EMAIL=EMAIL_OWNER_AKTIF
```

Setelah itu jalankan hanya seeder yang dimaksud sesuai panduan tersebut.

## 10. Jalankan scheduler dan queue lewat Cron Jobs

Shared hosting tidak dapat menjalankan `php artisan horizon` terus-menerus.

Buka **Websites → Dashboard → Advanced → Cron Jobs**. Jadwal hPanel memakai
UTC, sedangkan aplikasi menggunakan `Asia/Jakarta`.

Tambahkan custom cron scheduler dengan interval terkecil yang tersedia,
idealnya setiap menit:

```bash
/opt/alt/php83/usr/bin/php /home/u123456789/domains/demo.example.com/app/artisan schedule:run
```

Tambahkan custom cron queue dengan interval terkecil yang tersedia:

```bash
/opt/alt/php83/usr/bin/php /home/u123456789/domains/demo.example.com/app/artisan queue:work database --stop-when-empty --max-time=50 --tries=3
```

Opsi `--stop-when-empty` memastikan proses berhenti setelah antrean kosong,
sesuai batasan shared hosting. Notifikasi dapat terlambat mengikuti interval
cron.

Project menjadwalkan `horizon:snapshot` setiap lima menit. Karena Redis/Horizon
tidak aktif pada shared hosting, perintah tersebut dapat gagal dan tercatat di
log tanpa menghentikan request website. Untuk demo singkat ini dapat diabaikan;
deployment VPS tetap diperlukan jika Horizon ingin digunakan penuh.

## 11. Verifikasi demo

Jalankan melalui SSH:

```bash
cd /home/u123456789/domains/demo.example.com/app
/opt/alt/php83/usr/bin/php artisan about --only=environment
/opt/alt/php83/usr/bin/php artisan migrate:status
/opt/alt/php83/usr/bin/php artisan schedule:list
/opt/alt/php83/usr/bin/php artisan queue:work database --stop-when-empty --tries=3
```

Periksa melalui browser:

- HTTPS aktif dan tidak ada mixed content.
- halaman utama, login, dan dashboard dapat dibuka.
- tambah anggota dan relationship bekerja.
- foto profil tampil melalui `/storage`.
- family tree untuk data demo dapat dibuat.
- email atau notifikasi yang diperlukan demo berjalan.
- `/horizon` dan `/telescope` tidak dipakai untuk presentasi.

Periksa error pada:

```text
app/storage/logs/laravel.log
```

Jika muncul HTTP 500, sementara ubah `LOG_LEVEL=debug` tanpa mengaktifkan
`APP_DEBUG`, periksa log, lalu kembalikan ke `warning`.

## 12. Update demo

Backup database lewat hPanel sebelum update:

```bash
cd /home/u123456789/domains/demo.example.com/app
/opt/alt/php83/usr/bin/php artisan down --retry=60
git pull --ff-only origin main
composer2 install \
  --no-dev \
  --prefer-dist \
  --no-interaction \
  --optimize-autoloader \
  --ignore-platform-req=ext-pcntl
```

Build aset di komputer/CI, upload isi `public/build` ke `public_html/build`,
kemudian lanjutkan:

```bash
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan optimize
/opt/alt/php83/usr/bin/php artisan up
```

Jangan menyalin ulang `public/index.php` ke `public_html`, karena file versi
shared hosting mempunyai path bootstrap yang berbeda.

### Update otomatis via GitHub Actions

`.github/workflows/deploy.yml` menjalankan `scripts/deploy-hostinger.sh` lewat
SSH setelah workflow `CI` sukses pada branch `main` (atau manual lewat
**Run workflow**). Isi secret berikut di **Settings → Secrets and variables →
Actions**:

| Secret | Wajib | Keterangan |
| --- | --- | --- |
| `SSH_HOST` | ya | Hostname SSH dari hPanel |
| `SSH_PORT` | ya | Port SSH dari hPanel |
| `SSH_USER` | ya | Username SSH |
| `SSH_PRIVATE_KEY` | ya | Private key deploy (kunci publik terpasang di server) |
| `APP_DIR` | ya | Path absolut source Laravel di server |
| `PUBLIC_HTML` | tidak | Path `public_html` agar aset ikut disalin |
| `PHP_BIN` | tidak | Override binary PHP 8.3 (default `/opt/alt/php83/usr/bin/php`) |
| `COMPOSER_BIN` | tidak | Override Composer (default `composer2`) |

Server tetap harus merupakan git clone dengan remote `origin` yang benar, karena
script menjalankan `git pull --ff-only origin main`.

## 13. Batas penggunaan

Shared hosting cukup untuk walkthrough dengan sedikit pengguna dan dataset
terkontrol. Jangan memakainya untuk:

- uji performa 100.000 anggota
- worker queue real-time
- ekspor tree berukuran besar
- beban upload atau request bersamaan yang tinggi
- penyimpanan arsip keluarga produksi
- endpoint publik yang menerima traffic tanpa rate limit

Hapus atau nonaktifkan website demo setelah presentasi jika berisi data keluarga
asli. Gunakan data sintetis dan jangan tampilkan credential atau data pribadi
dalam sesi investor.

## Referensi

- [Hostinger: Composer pada Web dan Cloud hosting](https://www.hostinger.com/support/5792078-how-to-use-composer-at-hostinger/)
- [Hostinger: mengubah versi PHP](https://www.hostinger.com/support/1575755-how-to-change-the-php-version-of-your-hostinger-hosting-plan/)
- [Hostinger: koneksi SSH shared hosting](https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh-in-hostinger/)
- [Hostinger: konfigurasi Cron Jobs](https://support.hostinger.com/en/articles/1583465-how-to-set-up-a-cron-job-at-hostinger)
- [Hostinger: membuat symlink](https://www.hostinger.com/support/1583694-how-to-create-a-symlink-at-hostinger/)
