# Bulk Member Import and CSV Export

Status: delivered with the FT-API-501 task (2026-08-20).

## Purpose

`MemberBulkService` (`app/Services/MemberBulkService.php`) provides bulk member operations for large families:

- **Import** a CSV file of family members in one request.
- **Export** the full member directory as a downloadable CSV.

The service works exclusively with the `family_members` table and never touches relationships or derived kinship.

## Endpoints

### Import

`POST /api/v1/families/{family}/members/import`

- Authorization: Sanctum bearer token; owner or admin role (`FamilyPolicy::update`).
- Request: `multipart/form-data` with field `file` (CSV, max 2 MB).
- Rate limit: 10/min (`throttle:10,1`).
- Response `200`:

```json
{
  "success": true,
  "message": "Members imported",
  "data": {
    "imported": 2,
    "skipped": 1,
    "errors": [
      {
        "row": 3,
        "full_name": "Ani",
        "errors": { "gender": ["The selected gender is invalid."] }
      }
    ]
  }
}
```

### Export

`GET /api/v1/families/{family}/members/export`

- Authorization: Sanctum bearer token; any active family role (`FamilyPolicy::view`).
- Response `200`:
  - `Content-Type: text/csv; charset=UTF-8`
  - `Content-Disposition: attachment; filename="<slug>-members.csv"`
- Rows are streamed with a cursor so large families do not load into memory.
- Rate limit: 10/min (`throttle:10,1`).

## CSV format

The first row is a header; the `full_name` column is required. Comma or semicolon delimiters are auto-detected.

| Column | Required | Rules |
|---|---|---|
| `full_name` | yes | string, max 255 |
| `nickname` | no | string, max 255 |
| `gender` | no | `male` or `female` |
| `religion` | no | one of the religion catalog |
| `birth_date` | no | date (`YYYY-MM-DD`) |
| `birth_place` | no | string, max 255 |
| `is_alive` | no | `true`/`false`/`1`/`0`/`yes`/`no`; defaults `true` |
| `death_date` | no | date, must be on/after `birth_date` |
| `death_place` | no | string, max 255 |
| `biography` | no | string |
| `branch_uuid` | no | UUID of a branch in the same family |

Export adds the member `uuid` and `branch_name` columns. The exported file can be edited and re-imported.

## Rules and behavior

- Rows are validated independently. A row that fails validation is reported in `errors` and counted in `skipped`; valid rows are still imported.
- A `branch_uuid` that belongs to another family is rejected for that row.
- If `death_date` is present, `is_alive` is forced to `false`.
- The import runs in a single transaction; a database failure rolls back the whole import.
- Relationship and tree caches are invalidated and a `MEMBERS_IMPORTED` activity log entry is written.
- The import does **not** award gamification points (bulk contributions are not rewarded to avoid inflating leaderboards).
- Re-importing the same file creates duplicate members; the import does not de-duplicate by name or identity.

## Database and schema

No migration or schema change was required. `cursorForFamily` was added to `FamilyMemberRepositoryInterface` for streaming export, and `allForFamily` to `FamilyBranchRepositoryInterface` for branch resolution.

## Tests

- `tests/Unit/MemberBulkServiceTest.php` — valid import, per-row errors, branch scoping, activity log, missing `full_name` header rejection, CSV export.
- `tests/Feature/MemberBulkApiTest.php` — owner/admin import, member/outsider/unauthenticated rejection, owner/member export, outsider export rejection, invalid CSV 422.
