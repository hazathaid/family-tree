# Phase 16 API

## Health Check

`GET /api/v1/health`

Endpoint publik untuk load balancer dan uptime monitor. Endpoint dibatasi oleh rate limiter API dan tidak mengembalikan alamat host, kredensial, exception, atau detail infrastruktur.

Respons sehat (`200`):

```json
{
  "success": true,
  "message": "Healthy",
  "data": {
    "status": "ok",
    "checks": {
      "database": "ok",
      "redis": "ok"
    }
  }
}
```

Jika dependency tidak tersedia, endpoint mengembalikan `503`, `success: false`, dan status dependency `unavailable`.

Semua endpoint `/api/v1` dibatasi 60 request/menit per user atau IP. Login dibatasi lebih ketat menjadi 5 percobaan/menit per kombinasi email dan IP.
