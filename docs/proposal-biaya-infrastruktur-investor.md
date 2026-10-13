# Proposal Biaya Infrastruktur Tahunan

## Silsilahku

**Disiapkan untuk:** Presentasi investor  
**Tanggal:** 2 Agustus 2026  
**Mata uang:** Rupiah (IDR)

---

## Ringkasan Eksekutif

Family Tree dapat dipresentasikan kepada investor menggunakan shared hosting yang saat ini sudah aktif tanpa biaya infrastruktur tambahan. Setelah produk mulai digunakan publik, aplikasi disarankan berpindah ke VPS agar Laravel, MySQL, Redis, queue worker, scheduler, dan proses pembuatan family tree dapat berjalan dengan stabil.

Estimasi anggaran infrastruktur:

| Tahap | Estimasi tahunan | Tujuan |
|---|---:|---|
| Demo investor | Rp0 tambahan | Validasi produk dan presentasi |
| MVP publik | Rp1.613.700–Rp2.536.000 | Pengguna awal dan keluarga pertama |
| Produksi yang direkomendasikan | Rp2.033.700–Rp3.005.700 | Operasional publik dengan resource lebih aman |
| Tahap pertumbuhan | Sekitar Rp7.300.000 | Penyimpanan dan volume email lebih besar |

Harga merupakan estimasi per 2 Agustus 2026, belum termasuk pajak, biaya tenaga developer, administrasi server, dan perubahan kurs. Harga promo Hostinger dapat mensyaratkan pembayaran beberapa tahun di muka.

---

## Tahap 1 — Demo Investor

Tahap ini menggunakan shared hosting dan alamat situs sementara yang sudah tersedia.

| Kebutuhan | Layanan | Biaya per tahun |
|---|---|---:|
| Hosting aplikasi | Shared Hosting Hostinger aktif | Sudah dibayar |
| Database | MySQL Hostinger | Termasuk |
| Penyimpanan foto | Storage hosting | Termasuk |
| Email undangan | Laravel `MAIL_MAILER=log` | Gratis |
| Domain sementara | `hostingersite.com` | Gratis |
| SSL/HTTPS | Hostinger | Gratis |
| Source code | GitHub | Gratis |
| **Tambahan biaya** | | **Rp0** |

Pada mode demo, email undangan tidak dikirim ke inbox. Tautan undangan diambil dari log aplikasi melalui SSH, kemudian dibuka pada browser incognito untuk memperagakan proses anggota membuat password dan mengelola profilnya sendiri.

---

## Tahap 2 — MVP Publik

Tahap ini cocok ketika aplikasi mulai digunakan oleh keluarga pertama.

| Kebutuhan | Layanan | Estimasi per tahun |
|---|---|---:|
| Server Laravel | Hostinger VPS KVM 1 | Rp1.402.800 |
| Domain `.id` | Hostinger | Rp210.900 tahun pertama |
| Database | MySQL 8 pada VPS | Termasuk |
| Cache dan antrean | Redis pada VPS | Termasuk |
| Penyimpanan foto | Cloudflare R2, sampai 10 GB | Gratis |
| Email undangan | Resend, sampai 3.000 email/bulan | Gratis |
| CDN dan DNS | Cloudflare Free | Gratis |
| SSL | Let's Encrypt/Cloudflare | Gratis |
| Backup mingguan | Hostinger VPS | Termasuk |
| Monitoring dasar | Laravel log dan uptime monitor | Gratis |
| **Total estimasi tahun pertama** | | **Rp1.613.700** |

Hostinger mencantumkan harga promo KVM 1 sebesar Rp116.900 per bulan dan harga perpanjangan Rp193.900 per bulan. Dengan domain, estimasi biaya normal setelah promo sekitar Rp2.536.000 per tahun.

---

## Tahap 3 — Produksi yang Direkomendasikan

VPS KVM 2 menjadi pilihan yang lebih aman untuk menjalankan web, API, MySQL, Redis, queue worker, scheduler, pemrosesan foto, dan ekspor family tree dalam satu server.

| Kebutuhan | Layanan | Estimasi per tahun |
|---|---|---:|
| Server | Hostinger VPS KVM 2, 2 vCPU/8 GB RAM | Rp1.822.800 |
| Domain `.id` | Hostinger | Rp210.900 |
| Database | MySQL 8 pada VPS | Termasuk |
| Cache dan antrean | Redis pada VPS | Termasuk |
| Penyimpanan objek | Cloudflare R2, sampai 10 GB | Gratis |
| Email transaksional | Resend, sampai 3.000 email/bulan | Gratis |
| CDN, DNS, dan SSL | Cloudflare Free | Gratis |
| Backup database | Otomatis ke Cloudflare R2 | Masih dapat gratis |
| Monitoring awal | Sentry dan uptime monitor free tier | Gratis |
| **Total estimasi tahun pertama** | | **Rp2.033.700** |
| **Estimasi normal/perpanjangan** | | **Sekitar Rp3.005.700** |

---

## Tahap 4 — Pertumbuhan

Contoh anggaran ketika volume email melampaui 3.000 per bulan dan penyimpanan foto mendekati 100 GB:

| Kebutuhan | Estimasi per tahun |
|---|---:|
| Hostinger VPS KVM 2, harga normal | Rp2.794.800 |
| Domain | Rp210.900–Rp252.900 |
| Resend Pro, sampai 50.000 email/bulan | Sekitar Rp4.000.000 |
| Cloudflare R2, sekitar 100 GB | Sekitar Rp270.000 |
| **Total kasar** | **Sekitar Rp7.300.000** |

Perhitungan layanan berdenominasi dolar menggunakan asumsi kurs sekitar Rp16.500 per USD. Biaya aktual mengikuti kurs, pajak, pemakaian, dan harga penyedia pada waktu penagihan.

---

## Arsitektur Layanan yang Digunakan

| Komponen | Teknologi/Layanan | Fungsi |
|---|---|---|
| Domain dan DNS | Cloudflare | Pengelolaan DNS dan perlindungan dasar |
| Server | Hostinger VPS KVM 2 | Menjalankan aplikasi dan layanan backend |
| Web dan API | Laravel 12, PHP 8.3 | Business logic, autentikasi, dan REST API |
| Frontend web | Blade, Bootstrap 5, Vite | Antarmuka web responsif |
| Database | MySQL 8 | Data keluarga, anggota, artikel, dan event |
| Cache dan queue | Redis | Cache relationship/tree serta pekerjaan latar belakang |
| Penyimpanan foto | Cloudflare R2 | Foto profil, album, dan dokumen keluarga |
| Email | Resend | Undangan anggota dan email transaksional |
| SSL | Let's Encrypt/Cloudflare | HTTPS dan keamanan koneksi |
| Source control | GitHub | Penyimpanan kode dan alur deployment |
| Monitoring | Sentry dan uptime monitor | Pelacakan error dan ketersediaan aplikasi |
| Mobile | Flutter pada fase mobile | Aplikasi Android/iOS melalui REST API |

Supabase belum diperlukan karena aplikasi sudah menggunakan Laravel Authentication dan MySQL. Menambahkan Supabase pada tahap awal akan menggandakan database serta sistem autentikasi tanpa manfaat biaya yang berarti.

---

## Rekomendasi Keputusan

1. Pertahankan shared hosting untuk demo investor dengan biaya tambahan Rp0.
2. Setelah validasi atau pendanaan, pindahkan aplikasi ke Hostinger VPS KVM 2.
3. Gunakan paket gratis Resend dan Cloudflare R2 selama masih berada dalam batas pemakaian.
4. Siapkan anggaran infrastruktur Rp2–3 juta untuk tahun pertama produksi.
5. Siapkan anggaran Rp7–10 juta per tahun saat trafik, foto, dan email mulai bertambah.
6. Pisahkan biaya infrastruktur dari biaya pengembangan, pemeliharaan, keamanan, dan administrasi server.

---

## Sumber Harga

- Hostinger VPS: https://www.hostinger.com/id/hosting-vps
- Hostinger domain: https://www.hostinger.com/id/domain
- Cloudflare R2: https://developers.cloudflare.com/r2/pricing/
- Resend: https://resend.com/pricing
- Upstash Redis, sebagai opsi Redis terkelola: https://upstash.com/pricing/redis

Harga dapat berubah. Konfirmasi kembali harga checkout dan biaya perpanjangan sebelum melakukan pembelian.
