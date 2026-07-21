# Phase 14 Database

## `point_transactions`

Ledger poin yang menyimpan keluarga, pengguna, aksi, nilai poin, dan sumber aksi. Kombinasi aksi, tipe sumber, dan ID sumber unik agar satu kontribusi tidak mendapat poin dua kali. Index keluarga/pengguna mendukung profil dan leaderboard.

## `badges`

Katalog badge dengan kode unik, nama, dan deskripsi.

## `user_badges`

Badge yang diperoleh pengguna dalam konteks keluarga. Kombinasi keluarga, pengguna, dan badge unik. Semua tabel memiliki UUID dan timestamps; foreign key menggunakan cascade delete.
