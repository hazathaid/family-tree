# Deployment ke Hostinger VPS

> Untuk demo investor menggunakan shared hosting, gunakan panduan
> [`deployment-hostinger-shared.md`](deployment-hostinger-shared.md).

Panduan ini ditujukan untuk **Hostinger VPS dengan Ubuntu 24.04**, bukan
Hostinger Web/Cloud shared hosting. Aplikasi membutuhkan PHP 8.3, MySQL, Redis,
Laravel Horizon, dan scheduler yang harus berjalan terus-menerus.

Contoh berikut menggunakan:

- domain: `silsilah.example.com`
- direktori aplikasi: `/var/www/family-tree`
- user deployment: `deploy`

Ganti semua nilai contoh tersebut sebelum menjalankan perintah.

## 1. Siapkan VPS dan DNS

Pilih VPS yang mempunyai RAM minimal 4 GB untuk produksi awal. Tambahkan
kapasitas sesuai ukuran pohon, jumlah worker, dan beban ekspor.

Di hPanel, buka **Domains → DNS / Nameservers** dan buat record:

| Type | Name | Points to |
|---|---|---|
| A | `@` atau subdomain yang dipakai | IPv4 VPS |
| A | `www` (jika diperlukan) | IPv4 VPS |

Propagasi DNS dapat memerlukan waktu hingga 24 jam.

Di **VPS → Security → Firewall**, izinkan hanya:

- TCP 22 dari alamat IP administrator jika memungkinkan
- TCP 80 dari semua alamat
- TCP 443 dari semua alamat

Jangan membuka port MySQL `3306` atau Redis `6379` ke internet.

## 2. Masuk lewat SSH dan amankan server

Masuk sebagai `root` menggunakan detail dari halaman VPS:

```bash
ssh root@IP_VPS
```

Perbarui sistem dan buat user deployment:

```bash
apt update
apt upgrade -y
adduser deploy
usermod -aG sudo deploy
rsync --archive --chown=deploy:deploy ~/.ssh /home/deploy
```

Pastikan login user baru berhasil sebelum menutup sesi root:

```bash
ssh deploy@IP_VPS
```

Aktifkan firewall Ubuntu:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Setelah SSH key user `deploy` teruji, nonaktifkan login root dan autentikasi
password di `/etc/ssh/sshd_config`, kemudian muat ulang SSH. Jangan lakukan ini
sebelum login dengan SSH key benar-benar berhasil.

## 3. Instal runtime

```bash
sudo apt install -y \
  nginx mysql-server redis-server supervisor git unzip curl \
  php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-gd \
  php8.3-intl php8.3-mbstring php8.3-mysql php8.3-redis \
  php8.3-xml php8.3-zip
```

Pasang Composer dari installer resmi dan verifikasi checksum-nya mengikuti
petunjuk terbaru di <https://getcomposer.org/download/>.

Pasang Node.js 22 LTS untuk membangun aset frontend. Contoh menggunakan
NodeSource:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

Periksa versi:

```bash
php -v
composer --version
node --version
npm --version
mysql --version
redis-cli ping
```

`redis-cli ping` harus menghasilkan `PONG`.

## 4. Buat database

Amankan instalasi MySQL:

```bash
sudo mysql_secure_installation
```

Buat database dan user khusus aplikasi. Ganti password contoh dengan password
acak yang panjang:

```bash
sudo mysql
```

Jalankan SQL berikut:

```sql
CREATE DATABASE family_tree
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'family_tree'@'localhost'
  IDENTIFIED BY 'GANTI_DENGAN_PASSWORD_DATABASE_YANG_KUAT';

GRANT ALL PRIVILEGES ON family_tree.* TO 'family_tree'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 5. Ambil source code

Untuk repository private, daftarkan SSH key milik user `deploy` sebagai deploy
key pada penyedia Git.

```bash
sudo mkdir -p /var/www/family-tree
sudo chown deploy:www-data /var/www/family-tree
git clone URL_REPOSITORY /var/www/family-tree
cd /var/www/family-tree
```

Checkout branch atau tag produksi yang sudah lulus test:

```bash
git checkout main
```

## 6. Konfigurasi environment production

```bash
cp .env.example .env
chmod 640 .env
```

Edit `.env`:

```dotenv
APP_NAME="Silsilahku"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://silsilah.example.com
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=family_tree
DB_USERNAME=family_tree
DB_PASSWORD=GANTI_DENGAN_PASSWORD_DATABASE_YANG_KUAT

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

FILESYSTEM_DISK=public
TELESCOPE_ENABLED=false

MAIL_MAILER=smtp
MAIL_HOST=SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=SMTP_USERNAME
MAIL_PASSWORD=SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=silsilah.example.com
```

Untuk arsip foto produksi, gunakan object storage S3-compatible dan ubah
`FILESYSTEM_DISK` beserta credential terkait. Jangan commit `.env`, credential,
atau private key ke repository.

Instal dependency dan buat aset:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan storage:link
```

Atur izin Laravel:

```bash
sudo chown -R deploy:www-data /var/www/family-tree
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## 7. Jalankan migrasi

Ambil backup sebelum setiap migrasi produksi. Untuk deployment pertama:

```bash
php artisan migrate --force
```

Seeder demo tidak boleh dijalankan di produksi. Seeder Bani Fahruroji bersifat
opsional dan mempunyai prosedur tersendiri di
[`seeding-bani-fahruroji.md`](seeding-bani-fahruroji.md).

Optimalkan Laravel:

```bash
php artisan optimize:clear
php artisan optimize
```

## 8. Konfigurasi PHP-FPM dan Nginx

Sesuaikan batas upload PHP di `/etc/php/8.3/fpm/php.ini` karena dokumen dapat
mencapai 20 MB:

```ini
upload_max_filesize = 20M
post_max_size = 25M
memory_limit = 512M
max_execution_time = 120
```

Muat ulang PHP-FPM:

```bash
sudo systemctl reload php8.3-fpm
```

Buat `/etc/nginx/sites-available/family-tree`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name silsilah.example.com;

    root /var/www/family-tree/public;
    index index.php;

    client_max_body_size 25M;

    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan situs:

```bash
sudo ln -s /etc/nginx/sites-available/family-tree /etc/nginx/sites-enabled/family-tree
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 9. Aktifkan HTTPS

Setelah DNS mengarah ke VPS:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d silsilah.example.com
sudo certbot renew --dry-run
```

Certbot akan memperbarui konfigurasi Nginx dan memasang pembaruan sertifikat
otomatis.

## 10. Jalankan Laravel Horizon

Buat `/etc/supervisor/conf.d/family-tree-horizon.conf`:

```ini
[program:family-tree-horizon]
process_name=%(program_name)s
command=php /var/www/family-tree/artisan horizon
directory=/var/www/family-tree
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deploy
redirect_stderr=true
stdout_logfile=/var/www/family-tree/storage/logs/horizon.log
stopwaitsecs=3600
```

Aktifkan:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

Dashboard Horizon harus dilindungi oleh authorization gate. Jangan membuka
route Horizon ke publik sebelum otorisasinya diperiksa.

## 11. Aktifkan scheduler

Buka crontab user `deploy`:

```bash
crontab -e
```

Tambahkan satu baris:

```cron
* * * * * cd /var/www/family-tree && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler ini menjalankan reminder event, backup database, pembersihan backup,
monitoring backup, snapshot Horizon, dan pruning Telescope sesuai
`routes/console.php`.

## 12. Verifikasi deployment

```bash
cd /var/www/family-tree
php artisan about --only=environment
php artisan migrate:status
php artisan queue:monitor redis:default --max=100
php artisan schedule:list
sudo supervisorctl status family-tree-horizon
sudo nginx -t
curl -I https://silsilah.example.com
```

Periksa juga:

- registrasi, login, dan verifikasi email
- unggah foto dan dokumen
- pembuatan relationship dan family tree
- queue serta notifikasi
- endpoint API mobile
- log di `storage/logs/laravel.log`
- backup dapat dibuat dan dipulihkan

## 13. Prosedur update aplikasi

Jalankan hanya setelah perubahan sudah lulus test di CI atau staging:

```bash
cd /var/www/family-tree
php artisan down --retry=60
git pull --ff-only origin main
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan horizon:terminate
php artisan up
```

Supervisor akan menyalakan Horizon kembali setelah `horizon:terminate`.

Jika update gagal sebelum aplikasi aktif kembali:

```bash
php artisan up
```

Jangan rollback migration secara otomatis. Pulihkan versi aplikasi dan database
dari backup yang sudah diverifikasi sesuai rencana rollback release.

## 14. Backup dan pemeliharaan

- Simpan backup database dan media di lokasi di luar VPS, idealnya object
  storage dengan versioning.
- Uji restore secara berkala; backup yang belum pernah diuji belum dapat
  dianggap aman.
- Aktifkan update keamanan Ubuntu.
- Pantau ruang disk, RAM, CPU, error Nginx, PHP-FPM, MySQL, Redis, Horizon, dan
  queue yang gagal.
- Gunakan `php artisan backup:monitor` untuk memeriksa kesehatan backup.
- Jangan mengaktifkan `APP_DEBUG` atau Telescope untuk publik di production.

## Referensi

- [Hostinger: menghubungkan domain ke VPS](https://www.hostinger.com/support/1583227-how-to-point-a-domain-to-your-vps-at-hostinger/)
- [Hostinger: koneksi SSH ke VPS](https://www.hostinger.com/support/5723772-how-to-connect-to-your-vps-via-ssh-at-hostinger/)
- [Hostinger: managed VPS firewall](https://www.hostinger.com/support/8172641-how-to-use-a-managed-vps-firewall-at-hostinger/)
- [Laravel 12: deployment](https://laravel.com/docs/12.x/deployment)
- [Laravel 12: queues dan process monitor](https://laravel.com/docs/12.x/queues#supervisor-configuration)
- [Laravel 12: task scheduling](https://laravel.com/docs/12.x/scheduling#running-the-scheduler)
