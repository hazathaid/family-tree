# GEDCOM Family Import

Status: delivered with the FT-GEDCOM-002 task (2026-08-20).

## Purpose

`GedcomImportService` (`app/Services/GedcomImportService.php`) imports a family from a GEDCOM 5.5.1 LINEAGE-LINKED document. This closes the data-portability loop: files exported from this platform (see `docs/gedcom-export.md`) or from any external genealogy tool (Gramps, Ancestry, MyHeritage, FamilySearch) can be restored into a family.

The import never computes or stores derived kinship. It writes only the five base relationships (`father`, `mother`, `child`, `husband`, `wife`).

## Endpoint

`POST /api/v1/families/{family}/import/gedcom`

- Authorization: Sanctum bearer token; owner or admin role (`FamilyPolicy::update`).
- Request: `multipart/form-data` with field `file` (`.ged` text document, max 10 MB).
- Rate limit: 10/min (`throttle:10,1`).
- Response `200`:

```json
{
  "success": true,
  "message": "GEDCOM imported",
  "data": {
    "members_created": 42,
    "relationships_created": 58,
    "members_skipped": 1,
    "relationships_skipped": 0,
    "errors": []
  }
}
```

- Outsider/member role: 403; unauthenticated: 401; invalid document or missing file: 422 (JSON error envelope).

## Parsed records

| GEDCOM record | Mapped field |
|---|---|
| `INDI.NAME` | `family_members.full_name` (GEDCOM name `John /Doe/` becomes `John Doe`) |
| `INDI.SEX` (`M`/`F`) | `gender` (`male`/`female`) |
| `INDI.BIRT.DATE` | `birth_date` |
| `INDI.BIRT.PLAC` | `birth_place` |
| `INDI.DEAT.DATE` | `death_date` and `is_alive = false` |
| `INDI.DEAT.PLAC` | `death_place` |
| `INDI.NOTE` | `biography` |
| `FAM.HUSB` + `FAM.CHIL` | `father` edge (husband -> child) |
| `FAM.WIFE` + `FAM.CHIL` | `mother` edge (wife -> child) |
| `FAM.HUSB` + `FAM.WIFE` | `husband` edge (husband -> wife) and `wife` edge (wife -> husband) |

## Rules and behavior

- The document must start with `0 HEAD`, otherwise the request is rejected with a 422.
- GEDCOM dates are normalized: `12 MAY 2001` -> `2001-05-12`, `MAY 1990` -> `1990-05-01`, `1990` -> `1990-01-01`. Qualifiers (`ABT`, `EST`, `CAL`, `BEF`, `AFT`, `INT`, `FROM`, `TO`) are stripped. Unparseable dates are ignored.
- INDI records without a name are skipped and counted in `members_skipped`.
- Duplicate edges inside one file (the same couple/child pair referenced by more than one `FAM` record) are skipped and counted in `relationships_skipped`.
- The import runs in a single transaction; a failure rolls back the whole import.
- Relationship and tree caches are invalidated for the family; a `GEDCOM_IMPORTED` activity log entry is written.

## Known limitations

- The importer creates new member records. Re-importing the same file produces a parallel set of members; it does not match against existing members by name or xref.
- A member referenced in `FAM` records but with no `INDI` record is ignored.
- Multiple `FAM` records for the same couple are preserved as separate edges where they reference different children.
- Only GEDCOM 5.5.1 LINEAGE-LINKED documents are supported (no GEDZIP/`.zip` compressed GEDCOM).

## Database and schema

No migration or schema change was required. Members and relationships are created through `FamilyMemberRepositoryInterface` and `RelationshipRepositoryInterface`.

## Tests

- `tests/Unit/GedcomImportServiceTest.php` — individuals, life details, spouse edges, activity log, within-file dedup, unnamed skip, invalid document rejection, partial dates/names.
- `tests/Feature/FamilyImportApiTest.php` — owner/admin import, regular-member/outsider/unauthenticated rejection, invalid content 422, missing file 422.
