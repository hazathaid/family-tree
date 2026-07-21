# Mobile Phase 1 Service and Platform Documentation

Status: implemented 2026-07-22. Scope is FT-MOB-001 through FT-MOB-006 only.

## Platform and environment

Android/iOS runners and `mobile/pubspec.lock` are committed. Production IDs are `id.familytree.family_tree_mobile` and `id.familytree.familyTreeMobile`; development/staging use isolated suffixes. Android minimum is API 23, iOS minimum is 13, phone orientation is portrait-first, and release signing material is supplied outside Git.

`AppEnvironment` validates development/staging/production configuration at startup. Every API URL is absolute and ends in `/api/v1`; production rejects clear-text HTTP. Debug diagnostics expose only flavor, sanitized API host and connectivity.

Firebase is optional for a local debug startup and mandatory per environment when push is exercised. Native Firebase config and signing files are ignored. Setup and run commands live in `mobile/README.md`.

## Architecture and services

Dependency direction is `presentation -> application/domain contracts <- data`. Repository contracts and API adapters are feature-scoped. Shared configuration, HTTP, auth/session, secure storage, cache, error and widget infrastructure live under `lib/core`; theme/router/bootstrap live under `lib/app`.

`ApiClient` implements safe envelope parsing, 10-second connect timeout, 30-second send/receive timeout, Dio cancellation, typed error/status mapping and at most two jittered retries for idempotent GET requests. It honors numeric `Retry-After`. Mutations are not automatically retried. A 401 clears OS secure storage and all scoped cache through `SessionController`.

Pagination is represented by `PageData<T>` and retains explicit server metadata. `ApiResult<T>` separates success/failure without exposing internal payloads. Tokens use `flutter_secure_storage`; Hive is limited to bounded JSON read cache.

## Design, routing and offline behavior

The light design system implements audited color, spacing, radius, typography, >=48dp controls, adaptive phone/tablet navigation, text scaling through 200%, status/confirmation/snackbar, skeleton, empty/error and stale states. Widget and golden coverage records phone/tablet rendering.

GoRouter provides session bootstrap, auth, verification, onboarding, family-selection and protected target guards. It restores an intended location after authentication. Typed allowlisted paths exist for reset/verification, notification, member, article and event destinations. Domain screens remain placeholders where their implementation belongs to later phases; no Phase 2 behavior was added.

`ScopedCache` keys entries by user+family+query, stores timestamps, reports stale values, limits entries, and supports family/user/all clearing. Connectivity is only a hint; retry remains explicit. Risky mutations are never queued.

## API and database impact

Phase 1 adds no Laravel endpoint, migration, model, repository or server-side business rule. `docs/api-spec.md` and `docs/database-schema.md` therefore remain unchanged as authoritative contracts. Mobile infrastructure consumes the existing `/api/v1` envelope and public UUID policy.

## Verification coverage

- Environment validation and sanitized diagnostics.
- Immutable model parsing.
- Unauthenticated router/app rendering.
- Design-system minimum touch target.
- Phone/tablet golden for standard empty state.
- Flutter analyzer/tests and Android/iOS runner build commands.
