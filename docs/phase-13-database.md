# Phase 13 Database Documentation

Phase 13 tidak menambah tabel. Aplikasi mobile menggunakan tabel yang sudah tersedia melalui REST API:

* `personal_access_tokens` untuk autentikasi Sanctum.
* `families`, `family_members`, dan cache dashboard untuk ringkasan.
* `activity_logs` untuk timeline.
* `member_relationships` dan `member_tree_cache` untuk tree hasil BFS.
* `notifications` untuk inbox notifikasi.
* `push_device_tokens` untuk token Android/iOS.

Token push tetap dikelola Laravel dan memiliki UUID, unique token, platform, status aktif, timestamp, serta soft delete sesuai dokumentasi Phase 10. Tidak ada relasi turunan yang disimpan oleh aplikasi mobile.
