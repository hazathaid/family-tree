# Design: Cross-Family Link

Status: implemented web and mobile v1 (2026-10-21). Phase 1 (`XF-001`, data model + consent
workflow + service + policy + audit), Phase 2 (`XF-002`, cross-family resolver), Phase 3
(`XF-003`, read-only redacted linked tree), Phase 4 (`XF-004`, web surface), Phase 6
(`XF-005`, documentation/tests) and the REST/mobile surface (`XF-006`) are implemented.
Read-only across the link; pre-marriage grants (Phase 5) remain backlog.

## Problem

Two independent families (Pattern B in `family-membership-patterns.md`) cannot be connected.
Today a marriage is only representable by moving everyone into one family (Pattern A). Large
families with their own archives cannot link at a marriage point without merging records.

## Goal

Let two (or more) families stay independent yet connect at a marriage (the *link point*), so
that authorised members of either side can view the combined tree and resolve relationships
across the link — without duplicating member data and without merging family records.

## Non-goals

- Merging families into one `families` record (explicitly rejected; high risk, hard to undo).
- Storing derived kinship across families. Only the link plus existing base edges are stored.
- Making unrelated families visible to each other. Isolation stays intact except across an
  explicit, approved link.
- Public/anonymous access. This is authenticated, membership-scoped.

## Design decisions (locked)

Decided on 2026-10-13, before implementation:

- **Single canonical record per person.** Every person has exactly one `family_members` row,
  owned by one family. No duplication across families. The spouse's data is one record; the
  link makes it reachable from the other side.
- **One link per marriage suffices.** Reachability to the spouse's whole family (parents,
  siblings, children) is produced by BFS through the single link — no per-person bridge rows.
- **Read-only across the link (v1).** The non-owning family may view but never mutate linked
  members. All edits stay in the owning family. Cross-family write is a later phase.
- **Whole-family scope.** One approved marriage link opens the entire linked family, subject
  to living-member redaction and two-party consent.
- **Child home family.** Children of a cross-family marriage keep a single record in one
  *home family*, recorded on the link as `children_home_family_id`. Default is the family of
  the side that created the first child; siblings stay together in the same home. The other
  side views the children read-only through the link.
- **Pre-marriage view is a subset.** The consented, time-bound pre-marriage view
  (`pre-marriage-cross-family-view.md`) reuses this same link/redaction mechanism.

## Core concept: bridge node and canonical ownership

- Each person has one canonical record. The spouse lives in their own family; from the other
  family's tree they appear as a **bridge node** reached through the link, not a duplicate.
- The link connects two member records, one per family:
  `member_id` (family A) ↔ `linked_member_id` (family B), `link_type = marriage`.
- Because a marriage link is bidirectional in traversal, a single link also exposes the
  spouse's parents, siblings, and children to the other side via BFS, with no extra records.

## Data model (proposed)

New table `family_links`:

| Column | Notes |
|---|---|
| `id` | PK |
| `uuid` | unique public identifier |
| `family_id` | FK `families`, cascade; the initiating family |
| `linked_family_id` | FK `families`, cascade; the other family |
| `member_id` | FK `family_members`, cascade; member in `family_id` |
| `linked_member_id` | FK `family_members`, cascade; member in `linked_family_id` |
| `link_type` | enum: `marriage` (extensible later) |
| `children_home_family_id` | FK `families` nullable; home family for the couple's children |
| `status` | enum: `pending`, `active`, `revoked`, `expired` |
| `requested_by` | FK `users` |
| `approved_by_family` | FK `users` nullable; approver on `family_id` side |
| `approved_by_linked_family` | FK `users` nullable; approver on `linked_family_id` side |
| `expires_at` | timestamp nullable |
| `revoked_at` | timestamp nullable |
| `created_at`, `updated_at`, `deleted_at` | timestamps + soft deletes |

Indexes: unique `(family_id, linked_family_id, member_id, linked_member_id)`;
indexes on each FK and on `status`.

Integrity constraints:

- `member_id` must belong to `family_id`; `linked_member_id` must belong to `linked_family_id`.
- A member may appear in at most one active `marriage` link per side.
- Both families must consent before `status = active` (see workflow).

## Child ownership

- A child of a cross-family marriage has **one canonical record** in the couple's
  `children_home_family_id`.
- All children of the same couple share the same home family so siblings never split across
  families.
- The home family defaults to the side that creates the first child; it is stored on the
  link and may be changed by agreement (see Open questions).
- The other family reaches the children read-only through the link (`parent → link → spouse →
  child`).
- When adding a child, if a record for that child already exists in the other family, the
  system must **attach/link rather than duplicate** (see Open questions on reconciliation).

## Consent workflow (two-party approval)

1. A member (or owner/admin) of family A requests a link to a target member of family B.
2. `status = pending`; family B's owner/admin is notified.
3. Family B's owner/admin approves → both `approved_by_*` populated and `status = active`.
4. Either side may `revoke`; expired links (past `expires_at`) become `expired`.
5. Create / approve / revoke are logged as activity + audit events.

Only owner/admin may request or approve on behalf of a family. The members being linked are
notified.

## Relationship engine changes (proposed)

Current engine is single-family: `RelationshipTraversalService::shortestPath` guards same
family and the repository only returns family-scoped edges. To support cross-family
resolution:

- Treat an active `family_links` row as a bidirectional `spouse`-equivalent edge between the
  two member nodes (bridge).
- New service `CrossFamilyRelationshipResolverService` performs BFS over the combined graph
  (family-scoped edges + active links), then reuses
  `RelationshipResolverService::nameFromPath` for labels. No derived relationship is stored.
- The same-family guard becomes: "same family, **or** connected through active links".
- Paths may span families; each hop keeps its owning `family_id` for authorization checks.
- Depth is bounded (see Performance) to protect the 100,000-member target.

The base relationship storage and the five stored edge types are unchanged.

## Tree generation changes (proposed)

- `TreeGraphBuilderService::build($familyId)` stays single-family by default.
- Add an opt-in linked mode that includes bridged members/families up to N hops, marking
  boundary nodes with the link metadata so the UI can render a bridge affordance.
- `member_tree_cache` keys must include a link version so invalidation is correct when links
  change. A cross-family tree must never be served from a single-family cache entry.

## Visibility and redaction

- Cross-family traversal respects the same living-member redaction rules as any non-owner
  view (living members shown as anonymous placeholder nodes; PII suppressed).
- Viewing the linked side requires the caller to be a member of a linked family; unrelated
  families still receive 403/404.

## Caching and invalidation

- Link create/approve/revoke invalidates the affected families' relationship cache, tree
  cache, and generation map.
- Cross-family results are cached under a key that includes the participating link set
  version to avoid stale leakage across link changes.

## Impact on existing contracts

- `docs/family-relationship-engine.md` currently requires "cross-family denial". That becomes
  "denial unless connected by an active link". The verification matrix must be updated.
- `docs/prd.md` marks cross-family exposure as a reliability metric. A link is an explicit,
  consented exception; the metric must be reworded to "no exposure outside approved links".
- `docs/database-schema.md` and `docs/api-spec.md` require new entries once implemented.

## Performance

- Cross-family BFS must stay within the relationship target (<500 ms) for large families.
- Bound hop expansion across links (max linked families per traversal, max depth) and rely on
  indexed edge lookups. Record duration only, never names/path PII.

## Security design & containment

Cross-family visibility is treated as an **explicit, contained exception** to the
family-isolation invariant — never a global relaxation. The principles are default-deny,
least privilege, fail-closed, and full auditability.

### Single access gate

- All cross-family reads pass through one guard, `LinkedFamilyAccess`, which verifies an
  **active** link exists and the caller is a member of a linked family.
- Existing repositories keep their membership scoping (`whereHas('family.userRoles', ...)`)
  **unchanged**. Cross-family queries are only reachable through dedicated repository methods
  that require the guard.
- Any data reachable without an active link stays fully isolated. Unlinked access returns
  `403/404` with no resource data.

### Engine containment

- Do **not** modify `RelationshipTraversalService` or `RelationshipResolverService`. Add a
  separate `CrossFamilyRelationshipResolverService` so the single-family path is untouched.
- Existing relationship-engine regression tests must remain green; new cross-family tests are
  additive. The `coverage:gate` command (engine `>=95%`) enforces this.

### Least privilege / consent scope

- Read-only across the link in v1. No cross-family mutations.
- A link exposes tree structure and derived relationship labels only. Documents, biographies,
  contact fields, and media are **out of scope** unless separately opted in per category.
- Public boundary remains UUID-only: no internal numeric IDs, no raw paths, no edge lists.

### Privacy (UU PDP)

- Living-member redaction is default-on (anonymous placeholder nodes; PII suppressed).
- Two-party consent is mandatory before a link is active; either side may revoke at any time.
- Cross-family surfaces set `X-Robots-Tag: noindex`, apply a dedicated rate limiter, and block
  bulk export across a link.
- All link lifecycle actions (request, approve, revoke, expire) and cross-family access are
  written to activity and audit logs.

### Performance safety

- Hard hop and depth limits on cross-family BFS; links are indexed.
- Bounded traversal with timeout and a partial-tree fallback rather than unbounded expansion.
- Link-versioned cache keys; monitoring records duration only, never names or path PII.

### Data consistency

- Link lifecycle is transactional with cascading FKs.
- At most one active `marriage` link per member per side (service-enforced; MySQL has no
  partial unique index).
- Observers invalidate/re-point links when a linked member is merged, deleted, or moved.
- Child reconciliation attaches to an existing record instead of duplicating, reusing the
  `MemberMergeService` duplicate-detection heuristic.

### Rollout safety

- A feature flag disables all cross-family traversal instantly, reverting the app to strict
  isolation without a large rollback.
- Delivery is phased (see proposed tasks); only phases 2–3 touch the engine.

## Open questions

Resolved:

- Representation of the spouse: single canonical record + bridge node (no duplication).
- Cross-link permissions: read-only in v1.
- Approval: two-party (owner/admin of both families).
- Child home family: stored on the link (`children_home_family_id`), siblings kept together.

Still open:

1. Multiple simultaneous links per family (a family married into several others)?
2. Maximum linked-hop depth for traversal and tree rendering.
3. Lifetime: permanent link, or periodic re-confirmation / expiry?
4. May `children_home_family_id` change after children exist? Who approves?
5. Reconciliation when a child already exists as a record in the other family (attach vs
   duplicate-prompt), and how to handle a linked member being merged or deleted at home.
6. UI affordance for a bridge node and for requesting/approving links.

## Proposed tasks (backlog, phased order)

- **Phase 1 — Link foundation (no traversal)** ✅ Implemented (2026-10-13)
  - `XF-001` — `family_links` migration (incl. `children_home_family_id`) + model +
    `FamilyLinkService` + two-party approval + policy + audit logging. HTTP surface deferred
    to Phase 4.
- **Phase 2 — Cross-family resolution** ✅ Implemented (2026-10-13)
  - `XF-002` — `CrossFamilyRelationshipResolverService` (BFS across links); the single-family
    traversal/resolver services are left untouched for engine containment.
- **Phase 3 — Linked tree + redaction** ✅ Implemented (2026-10-13)
  - `XF-003` — `LinkedFamilyTreeService` reuses `TreePresentationService` and redacts living
    members (anchor excepted); link lifecycle invalidates relationship/tree caches.
- **Phase 4 — Web surface** ✅ Implemented (2026-10-13)
  - `XF-004` — `/family-links` request/search/approve/revoke and read-only
    `/family-links/{link}/partner-tree`, throttle `family-link` + `noindex`. The tree
    "add spouse" modal links into this page. Requesters may only target members of families
    they can access; approval still requires owner/admin of the linked family.
- **Phase 5 — Pre-marriage view**
  - `PRV-001..004` — consented, time-bound read-only subset (see
    `pre-marriage-cross-family-view.md`).
- **Phase 6 — Documentation and tests** ✅ Implemented (2026-10-21)
  - `XF-005` — updates to `prd`, `business-rules`, `database-schema`, `api-spec`, `tasks`;
    isolation, consent, redaction tests.
- **Phase 7 — REST API and mobile parity** ✅ Implemented (2026-10-21)
  - `XF-006` — `/api/v1/families/{family}/family-links` list/candidates/request, link
    approve/revoke, and `/api/v1/family-links/{link}/partner-tree`; Flutter `FamilyLinksScreen`
    and read-only partner-tree screen reuse the same server redaction. Mobile never computes
    kinship or cross-family access locally.

## Related documents

- `docs/design/family-membership-patterns.md`
- `docs/design/pre-marriage-cross-family-view.md`
- `docs/family-relationship-engine.md`
- `docs/tree-generation-engine.md`
