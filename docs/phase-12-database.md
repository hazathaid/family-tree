# Phase 12 Database

Phase 12 tidak menambah tabel atau kolom. Laporan merupakan agregasi read-only dari `family_members`, `member_relationships`, `activity_logs`, `member_photos`, dan `articles`.

Index yang dibuat pada phase sebelumnya (`family_id`, `created_at`, status, dan relasi anggota) digunakan kembali agar query tetap efisien. Relasi turunan dan level generasi tidak disimpan.
