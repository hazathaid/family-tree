# Phase 15 API - Administration

All endpoints require Sanctum authentication and the `super-admin` role. Public identifiers use UUIDs.

## User management

- `GET /api/v1/admin/users?per_page=15` lists users.
- `GET /api/v1/admin/users/{user_uuid}` shows a user.
- `PATCH /api/v1/admin/users/{user_uuid}` accepts `status=active|suspended`. Suspending revokes all API tokens; a super admin cannot suspend their own account.

## Family moderation

- `GET /api/v1/admin/families?per_page=15` lists families and content counts.
- `GET /api/v1/admin/families/{family_uuid}` reviews one family.
- `DELETE /api/v1/admin/families/{family_uuid}/content` accepts `content_type=article|photo|event` and `content_uuid`. Content must belong to the selected family and is soft-deleted.

## Audit logs

- `GET /api/v1/admin/audit-logs` lists logs.
- `GET /api/v1/admin/audit-logs/export` downloads at most 10,000 matching rows as CSV.

Audit filters: `action`, `auditable_type`, `date_from`, `date_to`, and `per_page` (1-100).

JSON endpoints use the standard `{success,message,data}` response envelope.
