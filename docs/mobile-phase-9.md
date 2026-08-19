# Mobile Phase 9 — Quality, Security, and Release

Status: implementation audit completed 2026-07-22. Repository-verifiable backend and web gates pass. Flutter SDK, production credentials, store consoles, and physical devices are not available in this environment; the affected release gates remain explicitly open.

## FT-MOB-801 — Automated test matrix

| Capability | Flutter coverage | Laravel contract/negative coverage |
|---|---|---|
| Auth, verification, onboarding, account | `app_test.dart`, `auth_screens_test.dart`, `models_test.dart` | `AuthApiTest`, `ProfileApiTest`, `FamilyApiTest` |
| Family switch/settings/roles/branches | `models_test.dart`, `design_system_test.dart` | `FamilyApiTest`, `FamilyDashboardApiTest`, `FamilyRoleApiTest`, `FamilyBranchApiTest` |
| Member CRUD and relationships | `member_phase4_test.dart`, `models_test.dart` | `FamilyMemberApiTest`, `RelationshipApiTest`, relationship unit/performance tests |
| Tree and export | `tree_phase5_test.dart`, `models_test.dart` | `FamilyTreeApiTest`, tree service/layout/performance tests |
| Article/photo/event/timeline/notification | `content_phase6_test.dart` | article, photo, event, timeline, and notification API tests |
| Search/reports/gamification | `discovery_phase7_test.dart` | search, report, and gamification API tests |
| Release/security configuration | `phase9_release_security_test.dart` | `ProductionReadinessTest` and family-isolation assertions across feature tests |

The full Laravel suite passes with 184 tests and 848 assertions. PHP code coverage cannot be measured because the runtime has no PCOV/Xdebug coverage driver. Flutter coverage cannot be measured because Flutter is not installed. The 80% global and 95% relationship/tree thresholds therefore remain unverified and FT-MOB-801 is not complete.

## FT-MOB-802 — Security and privacy audit

- Bearer tokens use `flutter_secure_storage`; Hive is bounded to non-sensitive read models and `SessionController.endSession` clears token and all cache.
- Production `API_BASE_URL` rejects HTTP. Android release additionally denies clear text and trusts system roots only; no certificate-bypass callback exists in Dart or native code.
- API errors are mapped to safe Indonesian messages. Request bodies, authorization headers, media bodies, and tokens are not logged.
- Deep links are limited to router-owned paths and still pass session/family guards. Article rendering strips executable/style markup and does not execute a WebView.
- Upload type/size checks remain advisory on mobile and authoritative in Laravel. Platform permissions contain purpose strings.
- Android notification previews use private lock-screen visibility. No Firebase service credential or signing key is tracked; the regression test scans common credential markers.
- Laravel negative tests cover outsider/cross-family access throughout family, member, relationship, tree, content, report, and administration APIs.

Open item: iOS cannot universally prohibit screenshots; sensitive values are not rendered after logout and app-switcher masking must be verified/implemented with a product-approved UX if required. Physical-device deep-link and notification privacy verification remains part of FT-MOB-805/806.

## FT-MOB-803 — Performance and reliability

Server lists remain paginated and bounded, tree rendering caps active widgets at 250, API retry is limited to idempotent requests, and upload/download operations expose cancellation/progress. The opt-in crash provider remains unconfigured because adding a mobile crash-reporting dependency requires explicit approval and production consent/DSN configuration.

Measured on the test container:

| Fixture | Result | Target |
|---|---:|---:|
| Relationship, 1,000 members, cached | 0.14 ms | <500 ms |
| Relationship, 10,000 members, cached | 0.17 ms | <500 ms |
| Relationship, 100,000 members, cold/cached | 17.60 / 0.18 ms | <500 ms cached |
| Web tree, 100-node fixture | Passed automated threshold | <5 s |

Startup, dashboard paint, scrolling jank, image memory, upload reliability, and tree rendering must be profiled in Flutter profile mode on representative Android/iOS hardware. FT-MOB-803 remains open until those measurements and consented crash reporting are delivered.

## FT-MOB-804 — Accessibility and localization

The implementation supports adaptive phone/tablet layouts, 200% text scaling, 48dp primary controls, semantic async states, labelled icon actions, semantic report rows, and a non-canvas tree list. Bahasa Indonesia is the current primary UI language.

Localization progress (2026-08-19): the ARB pipeline is now active (`l10n.yaml`, `lib/l10n/app_id.arb` as Indonesian template, `app_en.arb`, `generate: true`) and wired through `AppLocalizations.localizationsDelegates`/`supportedLocales` with `locale: Locale('id')` in `FamilyTreeApp`. The authentication flow (login, register, forgot/reset password, verification) and shared widgets (`AppSkeleton`, `AppErrorState`, `StaleDataBanner`, `AppStatusBadge`, `showAppConfirmation`) read all user-facing strings from ARB. `test/l10n_test.dart` verifies both locales resolve every key.

Remaining: screens outside auth/shared widgets still carry inline Indonesian strings and must be migrated to ARB in a follow-up; a complete screen-reader, external-keyboard, contrast, reduced-motion, and focus-order device audit has not been executed. FT-MOB-804 remains open.

## FT-MOB-805 — Android release readiness

Repository-ready: production flavor/application ID, release clear-text denial, system certificate policy, app/custom links, notification permission/channel, camera permission, icon/splash placeholders, and semantic version/build number.

External gates: inject production signing and `google-services.json`, publish `/.well-known/assetlinks.json`, provide final artwork/store metadata/privacy URL, then verify signed release install, upgrade, push, background handling, and deep links on real devices. FT-MOB-805 remains open.

## FT-MOB-806 — iOS release readiness

Repository-ready: production bundle ID/versioning, iOS 13 target, APNs and associated-domain entitlements, purpose strings, privacy manifest, icons/splash placeholders, and release configuration.

External gates: set Apple team/provisioning, inject production Firebase plist, publish the AASA file, confirm App Store privacy answers/metadata/artwork, archive through Xcode, then verify install, upgrade, APNs/background push, universal links, and permission prompts on real devices. FT-MOB-806 remains open.

## FT-MOB-807 — parity acceptance matrix

| Web capability in mobile scope | Flutter destination | REST contract | Automated evidence | Limitation |
|---|---|---|---|---|
| Auth/onboarding/account | auth flow, family selector, Account | `/auth/*`, `/profile/*`, `/families` | auth/account tests | Device deep links pending |
| Dashboard/family administration | `/`, `/family/manage` | family dashboard/assets/roles/branches | dashboard/family tests | None known |
| Members/relationships | `/members`, `/relationships`, `/relationship-resolver` | members, base relationships, resolver | Phase 4 + backend tests | None known |
| Family tree/export | `/tree` | generate, PNG, PDF | Phase 5 + backend tests | Device share/storage pending |
| Articles/photos/events | `/articles`, `/photos`, `/events` | content/media/event endpoints | Phase 6 + backend tests | Camera permission device test pending |
| Timeline/notifications | `/activity`, `/account/notifications` | timeline/notifications/push devices | Phase 6 + backend tests | Production push pending |
| Search/reports/gamification | `/search`, `/reports`, `/gamification` | search/report/gamification endpoints | Phase 7 + backend tests | None known |
| Super-admin | Web-only | `/admin/*` | administration tests | Intentionally out of scope |

Code and API mapping show no known in-scope web capability without a Flutter route and approved REST contract. End-to-end role/device acceptance is still required, so Phase 9 and parity are not marked complete.

## Reproducible quality gates

```bash
composer test
composer analyse
composer pint
npm run build
cd mobile && flutter analyze
cd mobile && flutter test --coverage
cd mobile && flutter build appbundle --release --flavor production \
  --dart-define=APP_FLAVOR=production --dart-define=API_BASE_URL=https://api.familytree.id/api/v1
cd mobile && flutter build ipa --release \
  --dart-define=APP_FLAVOR=production --dart-define=API_BASE_URL=https://api.familytree.id/api/v1
```

Do not commit signing files, Firebase configuration, API credentials, provisioning profiles, or store private keys.
