# Phase 9 Services - Events

## `EventService`

Menangani pembuatan, perubahan, soft delete, dan RSVP event melalui repository. Pembuatan event mencatat aktivitas `EVENT_CREATED`. Perubahan tanggal mengosongkan `reminder_sent_at` agar jadwal baru dapat diingatkan.

## `EventReminderService`

Mengambil event yang berlangsung dalam 24 jam dan belum pernah diingatkan. Setiap event diproses dalam transaksi dengan row lock, membuat satu notification untuk setiap anggota keluarga aktif, lalu menandai `reminder_sent_at`. Mekanisme ini mencegah duplikasi ketika worker berjalan bersamaan.

## Scheduler dan Queue

Job `SendEventReminders` mengimplementasikan `ShouldQueue` dan dijadwalkan tiap jam dengan overlap prevention. Production harus menjalankan Laravel scheduler dan Redis queue worker.

## Authorization

Semua pengguna aktif dapat melihat daftar event milik keluarga mereka. Hanya owner/admin dapat membuat event. Organizer atau owner/admin dapat mengubah dan menghapus event. Semua anggota keluarga dapat mengirim RSVP.
