# Family Membership Patterns

Status: design note (2026-10-13). Explains how Silsilahku models a family today and how
marriage-in relatives (spouse, in-laws) are represented. No implementation is defined here.

## Purpose

Answer the recurring product question: *"my spouse has their own family; how does that work
in this app?"* This document states the two supported patterns and their limits so future
feature work (see `cross-family-link.md`) starts from a shared understanding.

## Invariants that shape the model

- A family is an isolated graph. Relationships live as edges in `member_relationships` and
  every edge is scoped by `family_id`; a member belongs to exactly one `family_id`.
- The only persisted relationship types are `father`, `mother`, `child`, `husband`, `wife`
  (`app/Models/MemberRelationship.php`). Derived kinship (Mertua, Menantu, Pakde, ...) is
  computed by the Relationship Engine, never stored.
- The engine excludes cross-family edges and denies cross-family resolution
  (`docs/family-relationship-engine.md`, sections "Edge normalization" and "BFS").
- Family records and login accounts are distinct. A `family_member` may have no `user`, and a
  user may belong to many families (`docs/prd.md`, personas and roles).
- Membership (owner/admin/member) is per family via `family_user_roles`
  (`docs/investor-app-flow.md`, roles section). One account can hold different roles in
  different families.

## Pattern A — Single combined family (current default)

Everything that should appear connected lives in **one `families` row**.

- The spouse is added as a `family_member` in the same family and connected with a
  `husband`/`wife` edge.
- To make in-law labels appear, the spouse's parents are also added as members of that same
  family and connected as parents. The derived matrix then resolves `spouse,parent` → Mertua
  and `child,spouse` → Menantu (`docs/family-relationship-engine.md`, derived matrix).
- Result: one large, fully connected tree.

When to use: a family wants a single authoritative archive covering both sides of a marriage.

Trade-offs:

- All members share one UUID space, one branch structure, and one set of roles.
- The family grows large; every member is a first-class record in the same graph.
- There is no separation between "my family" and "my in-laws' family"; they are the same
  family record.

## Pattern B — Independent families (isolated)

A user creates or joins a second, separate `families` row.

- Supported today: a single account may belong to several families (`family_user_roles`),
  and onboarding selects the active family (`docs/app-usage-flow.md`).
- Not supported: any link or relationship between the two families. There is no cross-family
  edge, no cross-family tree, and no cross-family relationship label. From your family you
  cannot see or compute kinship to a member of your spouse's separate family.

When to use: the spouse wants a self-contained archive for their own lineage, independent of
yours.

Trade-offs:

- Full isolation and independent ownership.
- No connection whatsoever between the two archives.

## Comparison

| Concern | Pattern A — combined | Pattern B — independent |
|---|---|---|
| Spouse representation | One member in the shared family | Separate member in their own family |
| In-law labels (Mertua/Menantu) | Computed automatically | Not available across families |
| Tree | One connected tree | Two isolated trees |
| Cross-family visibility | N/A (same family) | None |
| Ownership/roles | Shared family roles | Independent family roles |
| Data duplication | None | None (but no link either) |

## What is not possible today

- Two separate families connected at a marriage point while both stay independent.
- Viewing your spouse's separate family tree from your own.
- Cross-family relationship resolution (e.g. "my wife's uncle").

These require the proposed cross-family link mechanism described in
`docs/design/cross-family-link.md`, which is **not implemented** and is tracked as backlog.

## Related documents

- `docs/family-relationship-engine.md` — base edges, BFS, derived matrix, isolation rules.
- `docs/prd.md` — personas, records vs accounts, cross-family non-goals.
- `docs/investor-app-flow.md` — per-family roles for a single account.
- `docs/design/cross-family-link.md` — proposed mechanism to connect independent families.
- `docs/design/pre-marriage-cross-family-view.md` — proposed consented, read-only preview.
