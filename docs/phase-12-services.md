# Phase 12 Services

`ReportService` mengatur statistik keluarga dan laporan aktivitas. Perhitungan generasi menggunakan graph dari `TreeGraphBuilderService` dan traversal BFS tanpa rekursi.

`ReportRepositoryInterface` memisahkan query agregasi dari business logic. `EloquentReportRepository` menghitung anggota, pengguna aktif, upload foto, dan artikel dengan scope keluarga serta periode laporan.
## Phase 7 REST parity

`ReportService` exposes the existing family statistics, activity summary, and web insight projections to mobile. `webInsights` remains the shared implementation for city, growth, and daily activity series, so controllers only authorize, normalize the requested date range, and return an API Resource. Every report cache key includes the internal family identifier and requested period and expires after 15 minutes.

`EloquentReportRepository` scopes every aggregate by `family_id` and excludes soft-deleted members/content where relevant. No schema or derived relationship is added.
