# Consolidated Business Rules

Status: audited against Laravel services, policies, requests and migrations on 2026-07-22. Laravel code is the runtime source of truth; conflicts below are tracked as issues.

## Identity, roles and isolation

- Guest access is limited to public/auth routes. Authenticated accounts must be active; verified email is required by web protected flows.
- Platform `super-admin` is distinct from family `owner`, `admin`, and `member` memberships.
- Every family-scoped query and mutation must verify membership/family identity. A UUID from another family returns denial/not-found without disclosing its data.
- A family has at least one owner; the last owner cannot be demoted or removed. Owners administer family roles; admin authority follows the relevant policy/service.
- Selecting an active family never grants membership and must be revalidated.

Sources: `FamilyPolicy`, family resource policies, `FamilyRoleService`, `EnsureActiveFamily`, family repositories.

## Members and base relationships

- A family member is a genealogical record, not automatically a user account.
- Required/current request constraints include first name and accepted gender/living values as implemented by `StoreFamilyMemberRequest`/`UpdateFamilyMemberRequest`.
- A member cannot relate to self. Both endpoints must belong to the same family.
- Only `father`, `mother`, `child`, `husband`, `wife` are persisted.
- At most one biological father and one biological mother is allowed; duplicate edges are rejected.
- Parent edges are cycle-checked before commit. Spouse edges are synchronized bidirectionally (`husband`/`wife`).
- Derived labels are computed by `RelationshipTraversalService` and `RelationshipResolverService`, never accepted as stored input.
- Relationship/member changes invalidate family relationship and tree cache.

Sources: `RelationshipService`, `RelationshipRepositoryInterface`, relationship requests, `FamilyMemberService`.

## Family tree

- Root is a member in the authorized family.
- Modes are `ancestor`, `descendant`, `full`; depth is 1–20.
- Generation uses iterative BFS and visited sets, not unbounded recursion.
- Layout is presentation over server graph; exports use the same authoritative generated tree.
- Deceased members remain visible and receive a memorial indicator.

Sources: `FamilyTreeService`, `TreeGraphBuilderService`, `TreeLayoutService`, tree requests/controllers.

## Content and publication

- Articles are family-scoped, have author/category/title/content, and status `draft`, `published` or `archived`.
- Draft/archived visibility and publish/feature operations follow `ArticlePolicy` and `ArticleService`; rich text is sanitized server-side.
- Comments and likes require authorized access to the article. Likes are idempotently unique per article/user.
- Categories are currently global records, not family-owned.
- Publication/moderation actions are logged where the service provides activity/audit logging.

Sources: article services/policies/requests and `RichTextSanitizer`.

## Media and privacy

- Allowed image extensions/MIME: jpg/jpeg, png, webp. Family photos max 10 MB; profile/avatar uploads max 5 MB; documents, if later implemented, max 20 MB and may include PDF.
- The server validates upload MIME/size and creates required thumbnails. Client validation is advisory only.
- Albums/photos/tags are family-scoped; only members of the same family may be tagged.
- Family privacy values supported by the actual family request/model govern public visibility; private data is never returned merely because a client hides it.

Sources: upload Form Requests, `MemberPhotoService`, `FamilyMemberService`, media policies.

## Events and RSVP

- Event title/date/organizer and family scope are authoritative server fields; status is `yes`, `no`, or `maybe`.
- One RSVP exists per event/user and may be updated.
- Reminder job selects eligible future events and prevents duplicate send through `reminder_sent_at`.
- Event create/update/delete/RSVP authorization follows `EventPolicy` and `EventService`.

## Notifications and push

- Notifications belong to one user; only that user may list/read them.
- Read sets `is_read` and `read_at`; read-all is scoped to the authenticated user.
- Push device token belongs to the authenticated user, platform is Android/iOS, and unregister/deactivate must not affect another user.
- User notification preferences are stored as JSON and currently have a web update flow but no REST endpoint.
- Payloads use safe target metadata and must not expose private family data on a locked screen.

Sources: `NotificationService`, `PushDeviceService`, profile web service/controller.

## Gamification, reports and search

- Points are awarded by server-defined actions and idempotent source keys; clients never submit authoritative point totals.
- Badges and leaderboards are derived server-side and family-isolated except the deliberate family leaderboard.
- Search/report filters and pagination execute server-side. Results are limited to families the actor may access.
- Reports expose aggregates/authorized activity, not private data from another family.

## Logging and security

- Critical mutations create activity and/or audit records through their services. Audit records are not user-editable/deletable.
- Form Requests validate every mutation; policies/gates authorize; controllers return safe envelopes.
- Rate limits include global API throttle plus stricter login, upload/export, comments/likes, RSVP, push and search limits declared in routes.
- Passwords/tokens/session payloads and internal exceptions are never returned or logged.

## Known rule/code discrepancies

| ID | Discrepancy | Resolution path |
|---|---|---|
| BR-I01 | Historical rules allow gender/living `unknown`; migration/request may use a different representation (`is_alive`). | Decide in an approved member-contract task; do not infer in Flutter. |
| BR-I02 | Historical minimum parent age gap/gender enforcement is not evidenced consistently in current `RelationshipService`. | Add only with product approval, migration/request/service tests. |
| BR-I03 | Duplicate detection/merge and member documents are described historically but not implemented. | Non-goal until backlog tasks exist. |
| BR-I04 | Privacy taxonomy in old docs may not exactly match actual family request enum/default. | API actual values govern; FT-API-202 must reconcile. |
| BR-I05 | Account session list/revoke and REST preferences are absent. | FT-API-101. |
| BR-I06 | Not every critical action currently has both activity and audit records. | Security hardening task must inventory coverage. |
