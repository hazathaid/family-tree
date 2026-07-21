# Phase 12 Services

`ReportService` mengatur statistik keluarga dan laporan aktivitas. Perhitungan generasi menggunakan graph dari `TreeGraphBuilderService` dan traversal BFS tanpa rekursi.

`ReportRepositoryInterface` memisahkan query agregasi dari business logic. `EloquentReportRepository` menghitung anggota, pengguna aktif, upload foto, dan artikel dengan scope keluarga serta periode laporan.
