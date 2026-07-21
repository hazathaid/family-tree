# Phase 10 Services - Notifications

## Alur

`NotificationService` membuat inbox record dan menjadwalkan `SendPushNotification` setelah transaksi database berhasil commit. Job mengambil seluruh perangkat aktif penerima dan menyerahkan payload ke `PushNotificationService`.

`PushNotificationService` menggunakan HTTP client bawaan Laravel dengan payload FCM v1-compatible. Satu format payload mendukung token Android maupun iOS tanpa business logic pada aplikasi Flutter.

`PushDeviceService` menangani registrasi dan penghapusan perangkat. Repository memastikan token unik dapat diregistrasi ulang setelah logout, pindah akun, atau soft delete.

`EventReminderService` dari Phase 9 sekarang menggunakan `NotificationService`, sehingga reminder event menghasilkan inbox notification sekaligus queued push.

## Konfigurasi

```env
PUSH_ENDPOINT=https://fcm.googleapis.com/v1/projects/{project-id}/messages:send
PUSH_ACCESS_TOKEN=short-lived-oauth-access-token
```

Jika konfigurasi kosong, inbox tetap dibuat dan job selesai tanpa request eksternal. Production harus memperbarui OAuth access token melalui secret/runtime configuration dan menjalankan Redis queue worker.

## Ekstensi

Service `notify()` menerima `type`, judul, body, dan metadata. Trigger ulang tahun, hari wafat, artikel, atau anggota baru dapat memakainya tanpa menambah logika pada controller maupun client mobile.
