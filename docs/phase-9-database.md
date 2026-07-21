# Phase 9 Database - Events

## `events`

Menyimpan event keluarga dengan UUID publik, relasi ke `families` dan organizer di `users`, waktu/lokasi, soft delete, serta `reminder_sent_at` untuk menjamin reminder tidak dikirim ulang. Index gabungan `family_id,event_date` mendukung daftar event dan index `reminder_sent_at,event_date` mendukung pencarian reminder jatuh tempo.

## `event_attendees`

Menyimpan RSVP dasar `yes`, `no`, atau `maybe`. UUID digunakan sebagai identifier publik. Unique index `event_id,user_id` menjamin satu RSVP per pengguna dan event.

## `notifications`

Menyimpan reminder in-app untuk anggota keluarga aktif. Relasi nullable ke event dan unique index `event_id,user_id` membuat pengiriman reminder idempoten. Kolom `is_read` disiapkan untuk penyajian notifikasi pada phase notifikasi.

Semua tabel memakai timestamps dan foreign key. Event memakai soft delete; attendee dan reminder dihapus secara cascade saat event dihapus permanen.
