# Product Requirements Document

Status: Phase 0 baseline (2026-07-22)
Source of truth: source code through web Phase 17, migrations through `2026_07_21_202050`, and `docs/tasks.md`.

## Product outcome

Family Tree Platform Indonesia preserves family identity, history, media, events, and kinship in a family-isolated web and mobile product. Capability parity means a user can complete the same supported outcome on mobile and web; mobile navigation and layout may be native and must not copy Blade screens pixel-for-pixel. Laravel remains authoritative for validation, authorization, relationship calculation, tree generation, sanitization, and all domain rules.

## Personas and roles

| Persona / role | Need | Authority |
|---|---|---|
| Guest | Discover, register, log in, recover password | Public/auth endpoints only |
| Family member | Browse permitted family data, engage with content, manage own account | Read family scope; create/update only where policy permits |
| Family admin | Curate members, base relationships, content, events, albums, branches and memberships | Family-scoped administration; cannot violate owner rules |
| Family owner | Govern family identity, access and administrators | Highest family authority; last active owner is protected |
| Super-admin | Platform health, user status, moderation and audit | Cross-family console, explicitly restricted |

Family records and login accounts are distinct: a `family_member` may have no `user`, and a user may belong to multiple families.

## Decisions

- Mobile scope is the family-member experience, including owner/admin controls inside the active family.
- The super-admin console remains web-only for the parity program. Mobile may show no super-admin navigation and must not call `/api/v1/admin/*`.
- Mobile targets Android and iOS and consumes `/api/v1` using Sanctum bearer tokens only.
- Public identifiers are UUIDs. Internal numeric IDs must not become mobile contracts.
- Mobile parity is capability equality, not visual duplication.
- Server-side pagination/search/filtering is mandatory for potentially large collections.

## Primary use cases

1. Register, verify email, recover password, establish a session, and manage account security.
2. Create/select a family; manage family identity, branches, memberships, roles and invitations.
3. Search, create and maintain family members and the five stored base relationships.
4. Resolve derived Indonesian kinship and inspect the server-produced path.
5. Browse ancestor, descendant and full trees; select root/depth/layout and export PNG/PDF.
6. Create and engage with articles, albums/photos, events/RSVP, timeline and notifications.
7. View reports, gamification and leaderboards appropriate to the active family.
8. Let a super-admin moderate users/content and inspect/export audit logs on web.

## Functional scope and task traceability

| Requirement | Delivery tasks |
|---|---|
| Specification and contract baseline | FT-DOC-001–007 |
| Mobile runtime, environments, architecture, design, routing, offline policy | FT-MOB-001–006 |
| Authentication, onboarding and account | FT-MOB-101–104; FT-API-101 |
| Dashboard and family administration | FT-API-201–202; FT-MOB-201–204 |
| Member and relationship workflows | FT-MOB-301–305 |
| Interactive tree and export | FT-API-301; FT-MOB-401–402 |
| Articles and engagement | FT-MOB-501–503 |
| Photos and albums | FT-MOB-601–603 |
| Events | FT-MOB-701–702 |
| Timeline, notification and push | FT-MOB-801–803 |
| Search and reports | FT-API-401; FT-MOB-901–903 |
| Gamification | FT-MOB-1001–1002 |
| Security, observability, accessibility, performance and release | Phase 11 tasks in `docs/tasks.md` |

## Web and mobile boundaries

Web remains the reference implementation for Phase 17 capability and retains the public landing page and super-admin console. Mobile includes member-facing authentication, family selection, dashboard, family settings allowed by role, members, relationships, tree, articles, photos, events, timeline, notifications, search, reports and gamification. Exact inclusion is gated by the REST contract; a Blade-only action must first receive an approved API task.

## Non-goals for parity

- Reimplementing Laravel domain rules in Dart.
- Storing derived relationships such as Pakde, Bude, cousin or in-law.
- Loading a complete 100,000-member family into device memory.
- Offline automatic replay of destructive or conflict-prone mutations.
- Mobile super-admin console.
- AI stories, photo restoration, DNA archive, grave QR, family map/book or voice archive.
- Schema changes hidden inside documentation recovery.

## Quality and security requirements

- Family isolation and policy checks apply on every scoped resource.
- Internal errors, credentials, tokens, sensitive headers and unnecessary PII never reach UI or logs.
- Uploads use the limits and MIME rules in `business-rules.md`.
- All list screens define loading, empty, error, offline, retry and pagination states.
- Mobile supports small phone, large phone and tablet; semantic labels, logical focus, text scaling and 48dp touch targets are required.
- Relationship lookup target is <500 ms, dashboard <2 s, tree generation <5 s; one family must support 100,000 members.

## Success metrics

Product: monthly/daily active users, activated families, members documented, content/event/photo contributions, tree views/exports, and 30-day retention. Reliability: crash-free sessions >=99.5%, API success rate >=99.9% excluding expected 4xx, p95 targets above, and zero confirmed cross-family data exposure. Parity completion: 100% of approved member-facing web capabilities mapped to a mobile flow or an explicitly tracked API gap, with release quality gates green.

## Open product decisions

- Account session-list/revoke and notification-preference REST behavior was approved and delivered by FT-API-101.
- Dashboard aggregation contract: FT-API-201.
- Family media/privacy settings exposed to mobile: FT-API-202.
- Tree layout/lazy expansion contract: FT-API-301.
- Generation-search/report contract: FT-API-401.
