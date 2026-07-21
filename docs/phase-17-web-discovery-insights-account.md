# Phase 17 Step 7 — Discovery, Insights, and User Account

## FT-1716 Global Search Web Interface

`GET /search` menampilkan hasil anggota, artikel, dan acara yang dikelompokkan dan dipaginasi. Filter lanjutan mencakup nama, kota, status hidup, serta generasi terhadap anggota akar. `SearchRequest` memvalidasi input dan `SearchService` membatasi query ke keluarga yang dapat diakses; controller web selalu memaksa keluarga aktif sebagai scope.

## FT-1717 Reports and Gamification Web Module

`GET /reports` menampilkan statistik anggota, generasi, kota, pertumbuhan, tren aktivitas, poin, badge, dan leaderboard keluarga. Filter `from` dan `to` menggunakan timezone aplikasi. Data laporan di-cache selama 15 menit per keluarga dan periode. Setiap grafik CSS memiliki tabel data yang setara untuk pembaca layar.

## FT-1718 User Profile, Preferences, and Security

Halaman `GET /profile` tersedia bagi pengguna terverifikasi dan menyediakan profil, avatar, preferensi notifikasi, perubahan password, serta daftar sesi database. Mengubah email dan password memerlukan password saat ini. Avatar dibatasi pada JPG/JPEG/PNG/WebP maksimum 5 MB. Perubahan password meregenerasi session ID dan mencabut token API lama.

Preferensi disimpan sebagai JSON pada `users.notification_preferences` dengan kunci `email_events`, `email_birthdays`, `email_articles`, dan `in_app_activity`.

## Verification

Coverage Step 7 berada di `WebDiscoveryInsightsProfileTest` dan `WebDiscoveryServiceTest`. Quality gate proyek tetap `composer test`, `composer analyse`, `composer pint`, dan `npm run build`.
