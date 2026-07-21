# Phase 14 API

Semua endpoint memerlukan bearer token Sanctum. Endpoint dengan parameter keluarga juga memerlukan keanggotaan keluarga.

## Profil gamifikasi

`GET /api/v1/families/{family_uuid}/gamification`

Mengembalikan total poin pengguna pada keluarga tersebut beserta badge yang sudah diperoleh.

## Leaderboard pengguna

`GET /api/v1/families/{family_uuid}/leaderboard?limit=20`

Mengembalikan peringkat pengguna berdasarkan total poin dalam satu keluarga. `limit` bersifat opsional dengan rentang 1–100.

## Leaderboard keluarga

`GET /api/v1/leaderboard/families?limit=20`

Mengembalikan peringkat keluarga berdasarkan akumulasi poin seluruh kontributornya.
