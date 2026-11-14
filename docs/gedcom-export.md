# GEDCOM Family Export

Status: delivered with the FT-GEDCOM-001 task (2026-08-19).

## Purpose

`GedcomExportService` (`app/Services/GedcomExportService.php`) exports a family as a GEDCOM 5.5.1 LINEAGE-LINKED document. The export is the data-portability / backup path: any external genealogy tool (Gramps, Ancestry, MyHeritage, FamilySearch, etc.) can import the produced `.ged` file.

The export never computes or stores derived kinship. It maps only the five base relationships (father, mother, child, husband, wife) into GEDCOM `INDI` and `FAM` records.

## Endpoints

### REST

`GET /api/v1/families/{family}/export/gedcom`

- Authorization: Sanctum bearer token; any active family role (`FamilyPolicy::view`).
- Response `200`:
  - `Content-Type: text/x-gedcom; charset=UTF-8`
  - `Content-Disposition: attachment; filename="<slug>-family-tree.ged"`
- Rate limit: 10/min (`throttle:10,1`).
- Outsider/unauthenticated: 403/401 respectively (JSON error envelope).

### Web

`GET /settings/export/gedcom` (inside `active.family` middleware)

- Any authenticated member of the active family; link exposed on the family settings page ("Ekspor GEDCOM").
- Non-member without an active family is redirected to onboarding.

## Document structure

```
0 HEAD
1 SOUR Silsilahku
1 GEDC
2 VERS 5.5.1
2 FORM LINEAGE-LINKED
1 CHAR UTF-8
1 SUBM @SUBM@
0 @SUBM@ SUBM
1 NAME <family name>
0 @I<id>@ INDI
1 NAME <full_name>
1 SEX M|F
1 BIRT
2 DATE <date>
2 PLAC <birth_place>
1 DEAT
2 DATE <date>
2 PLAC <death_place>
1 NOTE <biography>
1 FAMC @F<id>@
1 FAMS @F<id>@
...
0 @F<id>@ FAM
1 HUSB @I<id>@
1 WIFE @I<id>@
1 CHIL @I<id>@
1 DIV
2 DATE <end_date>
0 TRLR
```

## Relationship mapping

Base edges are normalized per edge direction:

| Stored edge (source -> target) | Meaning |
|---|---|
| `father` / `mother` | source is parent of target |
| `child` | source is child of target |
| `husband` | source is husband of target |
| `wife` | source is wife of target |

Families are then built in two passes:

1. **Parent families** — every child is grouped with its recorded parent set. Parents are emitted as `HUSB` when male (or gender unknown) and `WIFE` when female; the child becomes `CHIL`.
2. **Spouse families** — a husband/wife pair with no child-family is emitted as a `FAM` with `HUSB`/`WIFE` only. Pairs already covered by a parent family are not duplicated.

Per-member links: `FAMC` for every family the member is a child of, `FAMS` for every family the member is a husband or wife in. Soft-deleted members and relationships are excluded.

When a spouse edge has `end_reason = divorced` and an `end_date`, the family record also emits `1 DIV` and `2 DATE <end_date>`. Widowed marriages do not emit `DIV`; the deceased spouse is represented by the normal `DEAT` record.

### Polygamous households

A husband with multiple wives is exported as one `FAM` per wife. The shared husband receives one `1 FAMS` line per family, and each child receives a `1 FAMC` line pointing to the family that contains both its recorded father and mother. This keeps each wife's children attached to the correct couple and is valid GEDCOM 5.5.1 (multiple `FAMS` links per individual).

When a child has only one recorded parent, the child stays in a single-parent `FAM`. The exporter never guesses a missing mother or father, so polygamous data with incomplete parent edges is preserved rather than misattributed.

## Formatting rules

- Line endings are CRLF per the GEDCOM spec.
- Dates use `j M Y` uppercased to the GEDCOM month set (`12 MAY 2001`).
- Names and places are kept as-is; `@` is escaped as `@@`.
- Newlines inside biography become `1 CONT` lines; long values are wrapped at 190 chars using `1 CONC`, keeping every line <= 255 characters.

## Known limitations

- Indonesian names are emitted as a single given-name string (no `/surname/` split); external tools may treat the entire value as a given name.
- A parent with unknown gender is emitted as `HUSB`.
- Multiple parents of the same type or asymmetric parent sets can produce more than one family record for the same couple; the data is preserved but may render as separate families in external tools.
- GEDCOM import is not part of this task.

## Database and schema

No migration or schema change was required. The service reads `family_members` and `member_relationships` through the existing repositories (`FamilyMemberRepositoryInterface::allForFamily`, `RelationshipRepositoryInterface::allForFamily`).

## Tests

- `tests/Unit/GedcomExportServiceTest.php` — envelope, INDI/FAM structure, sex mapping, parent/spouse/child mapping, polygamous one-FAM-per-wife export with multiple `FAMS` and correct `FAMC`, divorced `DIV` export, escaping, multiline continuation, line-length budget, filename, soft-delete exclusion.
- `tests/Feature/FamilyExportApiTest.php` — owner/member export, outsider and unauthenticated rejection, web download, settings-page link, web outsider redirect.
