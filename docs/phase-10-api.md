# Phase 10 API - Notifications

Seluruh endpoint menggunakan Sanctum dan prefix `/api/v1`.

## In-app notifications

### GET `/notifications`

Mengambil notifikasi milik user aktif, terbaru lebih dahulu. Query opsional:

- `status`: `read` atau `unread`
- `limit`: 1-100, default 15
- `page`: nomor halaman

### POST `/notifications/{notification_uuid}/read`

Menandai satu notifikasi milik user sebagai sudah dibaca. Operasi ini idempotent.

### POST `/notifications/read-all`

Menandai seluruh notifikasi belum dibaca milik user dan mengembalikan jumlah record yang berubah.

## Push devices

### POST `/push-devices`

Mendaftarkan atau memperbarui token perangkat.

```json
{
  "platform": "android",
  "token": "device-provider-token"
}
```

`platform` menerima `android` dan `ios`. Token yang sama dipindahkan ke user terakhir yang mendaftarkannya untuk mencegah push masuk ke akun lama pada perangkat bersama.

### DELETE `/push-devices/{device_uuid}`

Menghapus token perangkat milik user aktif. User tidak dapat menghapus perangkat user lain.

Semua response mengikuti envelope `success`, `message`, dan `data` standar proyek.
