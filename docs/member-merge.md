# Member Duplicate Detection and Merge

Status: delivered with the FT-BE-007 task (2026-09-30), closing the duplicate-detection half of BR-I03.

## Purpose

`MemberMergeService` (`app/Services/MemberMergeService.php`) helps large families clean up accidental duplicate records:

- **Detect** candidate duplicate pairs inside one family.
- **Merge** a duplicate into a primary member in a single transaction.

Detection is advisory and heuristic; the merge is the authoritative, audited operation. Only the five base relationships are ever written or moved, and derived kinship is never stored.

## Endpoints

### Detect duplicates

`GET /api/v1/families/{family}/members/duplicates?limit=25`

- Authorization: Sanctum bearer token; owner or admin (`FamilyPolicy::update`).
- `limit`: optional, `1`–`100`, default `25`.
- Rate limit: 30/min (`throttle:30,1`).
- Response `200`:

```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "primary": { "uuid": "...", "full_name": "Budi Santoso" },
      "duplicate": { "uuid": "...", "full_name": "BUDI  santoso." },
      "confidence": "medium",
      "reasons": ["same_name"]
    }
  ]
}
```

- `confidence` is `high` when both records share the same birth date, `low` when both have different birth dates, otherwise `medium`.
- `reasons` may include `same_name`, `same_birth_date`, and `different_birth_date`.

### Merge

`POST /api/v1/family-members/{family_member}/merge`

- Authorization: Sanctum bearer token; owner or admin (`FamilyMemberPolicy::merge`).
- Request body: `{ "duplicate_uuid": "<uuid of the record to remove>" }`.
- The route member is the **primary** record that is kept.
- Rate limit: 10/min (`throttle:10,1`).
- Response `200`: the merged primary as a `FamilyMember` resource.
- Validation: `duplicate_uuid` is required, must be a UUID, and must exist.

## Merge rules

The merge runs in one database transaction:

1. **Account transfer** — if only the duplicate is linked to a user account, the link moves to the primary. Two different linked accounts are rejected (`422`); the merge is never automatic.
2. **Fill blanks** — nullable descriptive fields (nickname, gender, religion, birth date/place, biography, photos, branch) are copied from the duplicate only when the primary is empty. A deceased duplicate can pass its death details to a primary with no death information.
3. **Relationships** — all base relationship edges referencing the duplicate are repointed to the primary; self-edges are removed and duplicate edges (same source, target, and type) are collapsed to one.
4. **Photo tags** — `member_photo_tags` rows move to the primary; an existing tag for the same photo is not duplicated.
5. **Account invitations** — pending `member_account_invitations` for the duplicate are repointed to the primary.
6. **Removal** — the duplicate is soft-deleted.
7. **Caches and logging** — relationship, tree, and generation caches are invalidated and a `MEMBER_MERGED` activity log entry is recorded. The observer writes `family_member.updated` / `family_member.deleted` audit rows for the acting user.

Both members must belong to the same family; merging a member with itself is rejected.

## Database and schema

No migration or schema change is required. The service only reassigns existing `member_relationships`, `member_photo_tags`, and `member_account_invitations` rows and soft-deletes the duplicate.

## Known limitations

- Detection matches on normalized full names only (lowercase, punctuation and a small honorific stoplist removed). It does not perform fuzzy/phonetic matching, so differently spelled variants are not surfaced automatically.
- The total response is bounded by `limit`; a family with many identical names may need to page through candidates.
- Member documents remain unimplemented (the other half of BR-I03).

## Tests

- `tests/Feature/MemberMergeApiTest.php` — duplicate detection, owner/admin-only access, relationship/tag/account reassignment without duplicate edges, cross-family and conflicting-account rejection.
