# Phase 10 Database - Notifications

## `notifications`

Phase 10 memperluas tabel dari Phase 9 dengan:

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `type` | varchar, indexed | Jenis notifikasi, default `general` |
| `data` | json nullable | Metadata navigasi mobile/web |
| `read_at` | timestamp nullable | Waktu notifikasi dibaca |

Kolom `is_read` tetap dipertahankan untuk kontrak skema dan query unread yang efisien. Index `(user_id, is_read, created_at)` dari Phase 9 digunakan untuk inbox.

## `push_device_tokens`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | bigint PK | Internal identifier |
| `uuid` | uuid unique | Public identifier |
| `user_id` | FK users | Pemilik perangkat |
| `platform` | enum | `android` atau `ios` |
| `token` | varchar(512) unique | Token provider push |
| `is_active` | boolean | Status pengiriman |
| `last_used_at` | timestamp nullable | Registrasi/pemakaian terakhir |
| timestamps | timestamp | Audit waktu |
| `deleted_at` | timestamp nullable | Soft delete |

Foreign key user menggunakan cascade delete. Index `(user_id, is_active)` digunakan saat job memilih perangkat penerima.
