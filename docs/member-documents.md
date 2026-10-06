# Member Documents

Status: delivered with the FT-BE-008 task (2026-09-30), closing DB-001 and the document half of BR-I03.

## Purpose

`MemberDocumentService` (`app/Services/MemberDocumentService.php`) lets a family archive supporting documents for a member (identity cards, family cards, certificates, legal or medical records). Documents are family- and member-scoped and never affect the relationship graph.

## Endpoints

### List

`GET /api/v1/family-members/{family_member}/documents?limit=15`

- Authorization: any active family role for the member (`MemberDocumentPolicy::viewAny`).
- `limit`: optional, `1`–`100`, default `15`.
- Response `200`: a paginated `MemberDocumentResource` collection under `data`.

### Upload

`POST /api/v1/family-members/{family_member}/documents`

- Authorization: owner/admin, or the linked user managing their own profile (`MemberDocumentPolicy::create`).
- Request: `multipart/form-data`.

| Field | Required | Rules |
|---|---|---|
| `file` | yes | `jpg`, `jpeg`, `png`, `webp`, or `pdf`; max 20 MB |
| `title` | yes | string, max 255 |
| `category` | no | `identity`, `family_card`, `certificate`, `education`, `legal`, `medical`, `other` |
| `document_date` | no | date |
| `notes` | no | string, max 2000 |

- Files are stored on the `public` disk under `member-documents/{family_uuid}/{member_uuid}/`.
- Response `201`: the created `MemberDocumentResource`.
- Rate limit: 20/min (`throttle:20,1`).
- A `MEMBER_DOCUMENT_UPLOADED` activity log entry is written.

### Show and delete

- `GET /api/v1/member-documents/{member_document}` — any active family role.
- `DELETE /api/v1/member-documents/{member_document}` — the uploader or owner/admin; deletes the stored file and soft-deletes the row, writing `MEMBER_DOCUMENT_DELETED`.

### Download

`GET /api/v1/member-documents/{member_document}/download`

- Authorization: any active family role.
- Returns the file as an authenticated attachment with its stored MIME type and original filename.
- Rate limit: 30/min (`throttle:30,1`).

## Response shape

`MemberDocumentResource` exposes `uuid`, `family_uuid`, `member_uuid`, `title`, `category`, `original_name`, `mime_type`, `size`, `document_date`, `notes`, `download_url`, `uploaded_by` (`uuid`, `name`), and `created_at`. It never exposes the storage path.

## Rules

- Documents are always scoped to the member's family; cross-family access returns 403/404 without data.
- A linked user may manage only their own member profile; other members are read-only for them.
- Internal storage paths and numeric IDs are never returned; only the public UUID and an API download URL are exposed.

## Database

Migration `2026_09_30_000100_create_member_documents_table` creates `member_documents` with UUID, family/member/uploader foreign keys, file metadata, optional category/date/notes, soft deletes, and family/time plus member/time indexes.

## Tests

- `tests/Unit/MemberDocumentServiceTest.php` — store writes the row and file plus activity log; delete removes the file and soft-deletes the row.
- `tests/Feature/MemberDocumentApiTest.php` — owner upload/list/show/download/delete, linked-user scope, outsider denial, and type/size validation.
