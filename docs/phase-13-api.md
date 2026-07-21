# Phase 13 Mobile API

Aplikasi Flutter menggunakan REST API Laravel `/api/v1` dan Bearer token Sanctum. Tidak ada business logic keluarga yang diduplikasi di mobile.

## Endpoint yang digunakan

| Fitur | Method | Endpoint |
| --- | --- | --- |
| Login | POST | `/auth/login` |
| Session | GET | `/auth/me` |
| Logout | POST | `/auth/logout` |
| Daftar keluarga | GET | `/families` |
| Dashboard | GET | `/families/{family_uuid}/dashboard` |
| Timeline | GET | `/timeline?family_uuid={uuid}&limit=20` |
| Tree compact | GET | `/tree/generate?member_uuid={uuid}&mode=full&depth=3&layout=vertical` |
| Notifikasi | GET | `/notifications` |
| Tandai dibaca | POST | `/notifications/{uuid}/read` |
| Registrasi push | POST | `/push-devices` |

Login mengirim `device_name=family-tree-mobile`. Registrasi push mengirim `platform` (`android` atau `ios`) dan token provider. Semua respons mengikuti envelope `success`, `message`, dan `data`.

## Konfigurasi client

Base URL diberikan saat build:

```bash
flutter run --dart-define=API_BASE_URL=https://example.test/api/v1
```
