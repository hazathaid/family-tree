# Phase 8 API - Timeline

## Family Timeline

`GET /api/v1/timeline`

Requires a Sanctum bearer token. Only activities from families joined by the authenticated user are returned.

Query parameters:

| Parameter | Required | Description |
| --- | --- | --- |
| `family_uuid` | No | Limit results to one joined family. |
| `type` | No | `articles`, `photos`, `events`, or `members`. |
| `limit` | No | Items per page, 1–100; default 15. |
| `page` | No | Pagination page. |

Example response:

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "uuid": "activity-uuid",
      "family_uuid": "family-uuid",
      "type": "MEMBER_CREATED",
      "message": "Budi Santoso ditambahkan ke keluarga",
      "payload": {
        "subject_uuid": "member-uuid",
        "name": "Budi Santoso"
      },
      "user": {
        "uuid": "user-uuid",
        "name": "Admin"
      },
      "created_at": "2026-07-21T10:00:00.000000Z"
    }
  ]
}
```
