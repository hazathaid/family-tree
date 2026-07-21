# Phase 12 API

Semua endpoint memerlukan bearer token Sanctum dan keanggotaan pada keluarga terkait.

## Statistik keluarga

`GET /api/v1/families/{family_uuid}/reports/family-statistics`

Mengembalikan total anggota, anggota hidup/meninggal, jumlah generasi, dan jumlah anggota per level generasi. Level dihitung dinamis dari graph relasi dasar dengan BFS.

## Laporan aktivitas

`GET /api/v1/families/{family_uuid}/reports/activity`

Query opsional: `from` dan `to` dalam format tanggal. Periode default adalah 30 hari terakhir. Respons memuat pengguna aktif berdasarkan activity log, laporan upload foto, dan laporan artikel.
