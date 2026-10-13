# Design: Pre-Marriage Cross-Family Consent View

Status: proposed / backlog (2026-10-13). Not implemented. No migrations or code exist yet.

## Problem

Before marriage, a person (the *requestor*, who already has an account) wants to see their
partner's family tree read-only. Today the only ways to see another family are to be an
approved member of it (Pattern A / membership) or to accept a member-account invitation.

## Goal

Allow an authenticated requestor to view a partner's family **read-only**, through a
consent-based, time-bound, revocable grant issued by the partner's family. This is the
lightweight precursor to a full cross-family link (`cross-family-link.md`) and does not
connect the two families' graphs.

## Non-goals

- No public or anonymous access. The requestor must have an account (accountability +
  rate limiting).
- No stored cross-family relationship edges. Grant is a visibility grant only.
- No edit rights. Read-only.
- No merging of families.

## Decisions (locked)

- Consent-based grant, not blanket "any logged-in user may view any family".
- Time-bound and revocable.
- Living members are redacted (anonymous placeholder nodes; PII suppressed).
- Relationship labels are computed relative to the **anchor = the partner**
  (`relationship_to_anchor`) using the existing engine; BFS stays single-family.
- No derived relationship is persisted (AGENTS.md rule).
- Web (Blade) surface first; API/mobile parity is a separate task.

## Data model (proposed)

New table `family_view_grants` (mirrors the token/expiry pattern of
`member_account_invitations`):

| Column | Notes |
|---|---|
| `id` | PK |
| `uuid` | unique public identifier |
| `family_id` | FK `families`, cascade; the family being viewed |
| `anchor_member_id` | FK `family_members`, cascade; the partner (reference point) |
| `granted_by` | FK `users`; who issued the grant |
| `grantee_user_id` | FK `users` nullable; set once the grantee accepts |
| `invited_email` | string nullable; when the grantee has no account yet |
| `token_hash` | string(64) unique; only the hash is stored |
| `can_view_living` | boolean default false |
| `expires_at` | timestamp; mandatory |
| `revoked_at` | timestamp nullable |
| `last_accessed_at` | timestamp nullable |
| `created_at`, `updated_at`, `deleted_at` | timestamps + soft deletes |

Flow: if the grantee has no account, a token is emailed; after register/login and accept the
grant activates. If the grantee already has an account, the grant is created directly.

## Authorization

- A service guard `FamilyViewGrantService::authorize(User, Family)` — not `FamilyPolicy`,
  because this is not membership.
- Who may issue a grant: owner/admin of the target family, and/or the anchor member
  themselves (see Open questions).
- Grants are scoped to one family and one anchor. Cross-family probing still returns
  403/404 without data.

## Services

- `FamilyViewGrantService` — create / accept / revoke / list / expire, plus activity + audit
  logging.
- `ScopedFamilyViewService` — builds the redacted, anchor-centred view:
  - Reuses `TreePresentationService::present` / member queries, then redacts.
  - `is_alive = true` → hide `full_name`, `nickname`, `profile_photo*`, `birth_date`,
    `biography`, `user_id`; expose `display_name = "Anggota Hidup"`.
  - `is_alive = false` → full display.
  - Relationship labels via `RelationshipResolverService`; if a path endpoint is a redacted
    living node, the label is `kerabat`.
  - Never expose raw path, edge list, or internal numeric IDs to the grantee.

## Relationship handling

- Labels are resolved relative to the anchor (the partner), reusing
  `RelationshipResolverService::nameFromPath` within the target family's BFS.
- Because the view is single-family, the existing same-family guard remains valid; no
  cross-family traversal is required for this feature.

## Surface (web first)

| Route | Purpose |
|---|---|
| `GET /family-view/accept/{token}` | Authenticated acceptance of a grant |
| `GET /family-view/{family:slug}` | Redacted overview |
| `GET /family-view/{family:slug}/tree` | Read-only tree (no edit/add controls) |

- Thin controllers: `Web/FamilyViewGrantController`, `Web/ScopedFamilyViewController`.
- Middleware: dedicated `throttle:family-view` limiter and `X-Robots-Tag: noindex`.
- API/mobile parity is a separate, later task.

## Security and privacy

- New `family-view` rate limiter in `app/Providers/AppServiceProvider.php`.
- Activity/audit logging when a grant is created, accepted, revoked, or accessed.
- Grantee must be authenticated (accountability + rate limiting).
- Indonesian PDP: deceased-member data is still personal data; requires explicit consent,
  time bounds, and noindex.

## Caching

- Do **not** serve the grantee from `member_tree_cache` directly (it holds full PII).
- Cache the redacted view under a key scoped by family + grant scope, invalidated on member/
  relationship mutation, grant revoke, and family visibility change.

## Compatibility with the future cross-family link

- The grant/view is family-scoped today but the presentation + redaction layer
  (`ScopedFamilyViewService`) is decoupled so it can later serve a multi-family linked graph.
- When `cross-family-link.md` is implemented, the same redaction layer should be reused so
  bridge nodes are presented consistently.

## Open questions

1. Is the anchor member (the partner) shown unredacted, or subject to the living-redaction
   rule?
2. Who may issue a grant: owner/admin only, or the anchor member as well?
3. Default grant duration (e.g. 30 / 90 days).
4. May a grantee hold grants to two families (to align with the future cross-family link)?

## Proposed tasks (backlog)

- `PRV-001` — `family_view_grants` migration + model + `FamilyViewGrantService` + guard.
- `PRV-002` — `ScopedFamilyViewService` (redaction + anchor-relative relationships).
- `PRV-003` — web routes/controller/views + throttle/noindex.
- `PRV-004` — documentation (`prd`, `business-rules`, `database-schema`, `tasks`) + tests
  (`>=80%` coverage; isolation and redaction cases).

## Related documents

- `docs/design/family-membership-patterns.md`
- `docs/design/cross-family-link.md`
- `docs/family-relationship-engine.md`
- `docs/member-account-invitations.md`
