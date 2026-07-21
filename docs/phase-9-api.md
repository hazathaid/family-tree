# Phase 9 API - Events

Semua endpoint menggunakan prefix `/api/v1`, autentikasi Sanctum, dan format respons standar `success`, `message`, dan `data`.

## Endpoints

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| GET | `/events` | Daftar event dari keluarga pengguna |
| POST | `/events` | Membuat event; hanya owner/admin keluarga |
| GET | `/events/{uuid}` | Detail event dan daftar RSVP |
| PUT/PATCH | `/events/{uuid}` | Mengubah event; organizer atau owner/admin |
| DELETE | `/events/{uuid}` | Soft delete event; organizer atau owner/admin |
| POST | `/events/{uuid}/rsvp` | Membuat atau mengubah RSVP pengguna |

Filter daftar yang tersedia: `family_uuid`, `upcoming`, `search`, dan `limit` (maksimal 100).

## Create Event

```json
{
  "family_uuid": "4b22ba2b-9121-45a0-8f1d-c334800bf3bc",
  "title": "Reuni Keluarga",
  "description": "Reuni tahunan",
  "event_date": "2026-12-10 09:00:00",
  "location": "Bandung"
}
```

`event_date` wajib berada di masa depan. `title` maksimal 255 karakter.

## RSVP

```json
{
  "status": "yes"
}
```

Nilai status: `yes`, `no`, atau `maybe`. Satu pengguna hanya memiliki satu RSVP per event; request berikutnya memperbarui status yang ada.
