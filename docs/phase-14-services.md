# Phase 14 Services

`GamificationService` memberikan poin otomatis setelah aksi berhasil dan mengevaluasi badge secara idempoten. Aturannya:

| Aksi | Poin |
| --- | ---: |
| Tambah anggota | 10 |
| Upload foto keluarga | 5 |
| Menulis artikel | 15 |

| Badge | Syarat |
| --- | --- |
| Penjaga Sejarah | 10 upload foto |
| Penulis Keluarga | 5 artikel |
| Kontributor Aktif | 100 total poin |
| Ahli Silsilah | 25 anggota ditambahkan |

`GamificationRepositoryInterface` dan `EloquentGamificationRepository` menangani ledger poin, award badge, agregasi poin, dan query leaderboard. Controller hanya melakukan otorisasi, memanggil service, dan membentuk respons API Resource.
