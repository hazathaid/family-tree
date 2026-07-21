# Phase 16 Database

Phase 16 tidak menambah tabel domain. Laravel Telescope menambah tabel operasional berikut melalui migration:

- `telescope_entries`
- `telescope_entries_tags`
- `telescope_monitoring`

Data Telescope bukan sumber data bisnis dan dipangkas otomatis setiap hari setelah 48 jam. Backup database dijalankan setiap hari pukul 01:00, cleanup pukul 02:00, dan pemeriksaan kesehatan backup setiap jam.

Backup disimpan pada disk `BACKUP_DISK` dan dapat dienkripsi melalui `BACKUP_ARCHIVE_PASSWORD`. File `.env`, log, cache runtime, `vendor`, dan `node_modules` tidak ikut dalam arsip file.
