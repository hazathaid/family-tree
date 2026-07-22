# Technical Specification

Status: Phase 1 mobile foundation implemented, audited 2026-07-22.

## Runtime architecture

```text
Blade/Bootstrap web ─┐
Flutter mobile ──────┼─> Laravel HTTP controllers -> services -> repository contracts -> Eloquent/MySQL
                     └─> policies/requests/resources       └-> Redis cache/queue, object storage, push
```

Laravel owns domain behavior. Controllers adapt HTTP, Form Requests validate input, policies authorize, Resources serialize, services implement use cases, and repositories isolate persistence. Flutter owns presentation, input, navigation, session handling and bounded non-sensitive cache only.

## Current stack

- PHP 8.3, Laravel 12, Sanctum 4, Spatie Permission, Horizon, Telescope, Sentry and Backup.
- MySQL 8.x; Redis for cache/queue; S3-compatible filesystem by environment.
- Blade, Bootstrap 5 and small vanilla JavaScript web UI.
- Flutter/Dart with Riverpod, Dio, Hive, Firebase Core/Messaging. The current mobile folder is a prototype and has no committed platform runners.
- Vite builds web assets. Docker defines app, nginx, MySQL, Redis, Horizon worker and scheduler.

## Dependency rules

`presentation -> application/use case -> domain contracts <- data implementations`. Flutter widgets never depend directly on Dio/Hive/Firebase. Laravel controllers never query models or implement domain decisions. DTOs/models crossing layers are immutable. Generated JSON parsing must reject malformed required fields without exposing payload details.

## Flutter structure

```text
lib/app/{bootstrap,router,theme}
lib/core/{config,http,auth,storage,errors,logging,widgets}
lib/features/<feature>/{presentation,application,domain,data}
```

- Riverpod providers are feature-scoped and invalidated on logout/family switch.
- Repository interfaces live in domain; implementations live in data.
- Models use immutable values and explicit JSON mapping.
- Declarative routing provides auth, verification, onboarding, active-family and role guards.
- Tokens use OS secure storage; Hive contains only bounded, non-sensitive, family/user-keyed cache.

## Environments and flavors

Development, staging and production each require a distinct API base URL, application/bundle identifier and Firebase project/configuration. Configuration is injected at build time, validated at startup, and contains no secret. Production rejects clear-text HTTP. Debug diagnostics may show environment, sanitized base host and connectivity, never credentials/token/PII. Android/iOS IDs, minimum OS and signing are decided in FT-MOB-001/002; signing material is never committed.

## HTTP/session contract

- Base path `/api/v1`; `Accept: application/json`; Sanctum `Authorization: Bearer <token>`.
- Connect timeout 10 s, receive/send timeout 30 s (uploads/downloads may use an explicit 120 s operation timeout).
- Retry at most two times with jittered backoff only for idempotent requests and transient network/408/429/5xx responses; honor `Retry-After`. Never automatically retry non-idempotent mutations unless an idempotency contract exists.
- Cancellation follows disposed screens/search replacement.
- A 401 clears token and scoped cache, invalidates providers, and routes to login while retaining a safe intended deep link. Current API has no refresh-token endpoint; do not invent refresh semantics.
- 403, 404, 422 and 429 map to authorization, not-found, field-validation and rate-limit states; 5xx becomes a generic retryable message.
- Pagination parsing preserves server metadata and never fetches all pages implicitly.

## Deep links and notifications

Allowlisted links cover email verification, password reset, article, event, member and notification target. Links are parsed into typed destinations, then pass normal auth/family/policy guards. Firebase messages contain opaque target type/UUID, not sensitive records. Foreground/background/tap behavior deduplicates by message ID. Unknown targets open a safe notifications screen.

## Upload and download

Validate extension, MIME and size locally for feedback and again authoritatively on Laravel. Stream multipart uploads with progress and cancellation. Downloads use authenticated streaming, derive filename/content type from safe response headers, write to app cache/documents, verify non-empty size, and use platform share/open APIs. Never log file bodies or signed URLs. Binary tree endpoints are not decoded as JSON.

## Offline and caching

Cache only safe read models with user+family+endpoint/query keys, TTL and a bounded size/LRU policy. Show stale timestamps. Reads may fall back to stale data; risky mutations are not queued automatically. Logout/account switch deletes bearer token and all scoped cache. Connectivity is a hint; actual requests determine reachability.

## Safe logging and observability

Structured logs use correlation/request IDs, environment, operation and sanitized status/duration. Redact authorization/cookies/passwords/tokens, email/phone, request bodies, media URLs and relationship biographies. Production crash reporting samples breadcrumbs without PII. Laravel Horizon/Telescope are access-controlled and Telescope is disabled or tightly gated in production.

## CI/CD quality gates

Install locked dependencies, run `composer test`, `composer analyse`, `composer pint`, `npm run build`, `flutter analyze`, `flutter test`, secret scanning and dependency audit. Release adds signed Android/iOS builds, environment smoke tests, migration review, rollback artifact and store metadata. Deploy migrations before compatible app traffic and preserve backward-compatible API contracts during mobile rollout.

## Phase 3 implementation

- The family/user-isolated dashboard endpoint is the single mobile aggregate for six statistics, recent activity, birthdays, events, notifications, facts and recent members. Every collection is server-bounded and cached for five minutes.
- Flutter Phase 3 adds adaptive dashboard cards and family management tabs for identity/assets, branches and access. Owner/admin visibility is advisory; Laravel policies remain authoritative.
- Logo and cover uploads use multipart requests and the existing image picker dependency. Privacy is displayed as the fixed membership-only runtime rule; personal notification preferences remain under Account.

## Phase 4 implementation

- A dedicated member repository uses paginated member/relationship endpoints and the UUID-only resolver contract.
- The adaptive directory, detail, form/photo/delete, five-type relationship manager, and resolver remain scoped to the active family and Laravel authorization.
- Flutter displays derived kinship/path verbatim; Laravel retains graph traversal, cycle checks, inverse spouses, logging, and cache invalidation.

## Phase 5 implementation

- `TreePresentationService` aligns all four layouts, expansion metadata and server relationship labels for generate/export.
- Flutter uses a tree repository, repeat-and-replace depth expansion, a 250-node render budget, pan/zoom/focus controls and a semantic list alternative.
- Authenticated PNG/PDF byte streams support progress/cancellation; PNG preview and native sharing use `share_plus` without broad shared-storage writes.

## Phase 6 implementation

- Flutter adds a typed content repository for server-paginated articles, comments/likes, albums/photos/tags, events/RSVP, timeline and notifications.
- Article input emits an escaped paragraph-only HTML subset; Laravel sanitization and policies remain authoritative. Rendered content removes executable/style markup and never opens arbitrary HTML URLs.
- Media selection provides camera/gallery preview, advisory 10 MB/type checks, multipart progress, album/capture metadata and same-family member tags. Event times are converted to the device timezone and labelled explicitly.
- Timeline and notification payloads navigate only through an allowlisted resource-type-to-route mapping. Push permission and registration remain post-login; device removal and scoped cache cleanup remain part of logout/session handling.

## Phase 7 implementation

- A typed discovery repository owns grouped server-paginated search, family report aggregates, gamification profile, and leaderboards.
- Generation filters send the selected root UUID to Laravel; Dart displays the returned generation and never traverses the family graph.
- Reports request statistics, activity, and insight series concurrently. Period values are device-local calendar dates and the visible filter identifies the device timezone.
- Points, badges, awards, and ranks are rendered verbatim from the server. Charts use bounded progress visuals with equivalent labelled rows for assistive technology.

## Current gaps

- Phase 7 discovery, reports, and gamification parity is implemented. Phase 8 is closed as not applicable because administration remains intentionally web-only; Flutter has no super-admin navigation/screens and must not call `/api/v1/admin/*`.
- No refresh-token API; expiry means re-login.
- Firebase native configuration is absent and must remain environment-specific.
- Existing prototype stores the token in memory and has ad-hoc navigation; it is not the target architecture.
- Tree exports execute synchronously despite historical docs recommending queues; preserve actual API until an approved change.

## Phase 9 release audit

Android production denies clear-text traffic, declares only required runtime capabilities, and uses a private notification channel. iOS declares APNs/universal-link entitlements, privacy manifest, and permission purpose strings. `docs/mobile-phase-9.md` is the release evidence and parity matrix; it also records external device/signing/Firebase gates that must not be represented as passed by repository configuration alone.
