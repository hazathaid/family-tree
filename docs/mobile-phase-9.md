# Mobile Phase 9 — Quality, Security, and Release

Status: repository-side release gates strengthened 2026-10-21. Backend/web gates pass, and
unsigned Android/iOS release builds are reproducible through `.github/workflows/release-gate.yml`.
Phase 9 is **not complete** because four gates still require work outside this repository:

1. Flutter line coverage is 24.37% (target 80%). Backend coverage gate already enforces 80%/95%.
2. Mobile profiling and screen-reader/accessibility audits need real Android/iOS devices.
3. Production signing, Firebase files, AASA/assetlinks, store metadata, and privacy URLs need
   external credentials and consoles.
4. End-to-end role/device acceptance (FT-MOB-807) has not been executed.

## FT-MOB-801 — Automated test matrix 🟡 Coverage target open

| Capability | Flutter coverage | Laravel contract/negative coverage |
|---|---|---|
| Auth, verification, onboarding, account | `app_test.dart`, `auth_screens_test.dart`, `models_test.dart` | `AuthApiTest`, `ProfileApiTest`, `FamilyApiTest` |
| Family switch/settings/roles/branches | `models_test.dart`, `design_system_test.dart` | `FamilyApiTest`, `FamilyDashboardApiTest`, `FamilyRoleApiTest`, `FamilyBranchApiTest` |
| Member CRUD and relationships | `member_phase4_test.dart`, `models_test.dart` | `FamilyMemberApiTest`, `RelationshipApiTest`, relationship unit/performance tests |
| Tree and export | `tree_phase5_test.dart`, `models_test.dart` | `FamilyTreeApiTest`, tree service/layout/performance tests |
| Cross-family link and pre-marriage view | `family_link_phase_test.dart` | `FamilyLinkApiTest`, `FamilyViewGrantServiceTest`, `ScopedFamilyViewServiceTest`, `WebFamilyViewTest` |
| Article/photo/event/timeline/notification | `content_phase6_test.dart` | article, photo, event, timeline, and notification API tests |
| Search/reports/gamification | `discovery_phase7_test.dart` | search, report, and gamification API tests |
| Release/security configuration | `phase9_release_security_test.dart`, `phase9_release_gate_test.dart`, `l10n_parity_test.dart` | `ProductionReadinessTest` and family-isolation assertions |

Current measurements (2026-10-21):

| Suite | Result |
|---|---|
| Laravel | 383 tests, 1,634 assertions, passing |
| Flutter | 48 tests, passing |
| Flutter line coverage | 24.37% (1,359/5,577, generated l10n excluded) — **below 80%** |
| Backend coverage | Last Xdebug measurement 87.79% global / 98.09% engine (CI re-runs the gate) |

The release gate command is now available and intentionally fails until the Flutter target is met:

```bash
cd mobile
flutter test --coverage
cd ..
python3 scripts/check-flutter-coverage.py \
  --lcov mobile/coverage/lcov.info --min 80 --exclude-prefix lib/l10n/
```

## FT-MOB-802 — Security and privacy audit 🟡 Device verification pending

Repository-verifiable controls pass:

- Bearer tokens use `flutter_secure_storage`; cache clearing on logout/account switch is tested.
- Production `API_BASE_URL` rejects HTTP; Android release denies cleartext and trusts system
  roots only; iOS has no arbitrary-load exception.
- Errors are mapped to safe Indonesian messages; credentials/tokens/media are not logged.
- Deep links are router-owned and still pass session/family guards.
- Uploads are advisory on mobile and authoritative in Laravel (type/size validation).
- Android notification previews use private lock-screen visibility.
- No signing key, Firebase service credential, or private key is tracked; the credential scan
  in `phase9_release_security_test.dart` covers common markers.
- Laravel negative tests cover outsider/cross-family access, including the new family-link and
  family-view surfaces.

Remaining: physical-device deep-link/notification privacy verification, and an approved
app-switcher masking UX if the product requires screenshot protection on sensitive screens.

## FT-MOB-803 — Performance and reliability 🟡 Mobile profiling pending

Server lists are paginated and bounded, tree rendering caps active widgets at 250, API retry is
limited to idempotent requests, and uploads/downloads expose cancellation/progress. Crash
reporting remains opt-in with PII scrubbing and an overridable no-op reporter.

Recorded backend thresholds:

| Fixture | Result | Target |
|---|---:|---:|
| Relationship, 1,000 members, cached | 0.14 ms | <500 ms |
| Relationship, 10,000 members, cached | 0.17 ms | <500 ms |
| Relationship, 100,000 members, cold/cached | 17.60 / 0.18 ms | <500 ms cached |
| Web tree, 100-node fixture | Passed automated threshold | <5 s |

Remaining: Flutter profile-mode startup, dashboard paint, scrolling jank, image memory, upload
reliability, and tree rendering on representative Android/iOS hardware. A real Sentry DSN must
be injected at release time only after product approval.

## FT-MOB-804 — Accessibility and localization 🟡 Device audit pending

Implemented: adaptive phone/tablet layouts, 200% text scaling, 48dp primary controls, semantic
async states, labelled icon actions, semantic report rows, non-canvas tree lists, and full ARB
migration (id/en) across presentation screens. `l10n_parity_test.dart` now enforces identical
keys and placeholder metadata; both locales resolve every key.

Remaining: complete screen-reader, external-keyboard, contrast, reduced-motion, and focus-order
audit on physical devices.

## FT-MOB-805 — Android release readiness 🟡 External gates pending

Repository-ready: production flavor/application ID, fixed versioning, release clear-text
denial, system certificate policy, verified app links (`familytree.id`), custom scheme,
notification permission/channel, camera permission, icon/splash placeholders, and an unsigned
release bundle built in CI and locally.

External gates: inject production signing and `google-services.json`, publish
`/.well-known/assetlinks.json`, provide final artwork/store metadata/privacy URL, then verify
signed install, upgrade, background push, and deep links on real devices.

## FT-MOB-806 — iOS release readiness 🟡 External gates pending

Repository-ready: bundle IDs per flavor, deployment target **iOS 15.0** (raised from 13.0 for
current Xcode toolchains), versioning, APNs/associated-domain entitlements, purpose strings,
privacy manifest, URL scheme, portrait phone/iPad orientations, and a successful unsigned
release build.

External gates: set Apple team/provisioning, inject production Firebase plist, publish the AASA
file, confirm App Store privacy answers/metadata/artwork, archive through Xcode, then verify
install, upgrade, APNs/background push, universal links, and permission prompts on real devices.

## FT-MOB-807 — Parity acceptance matrix 🟡 Device/role acceptance pending

| Web capability in mobile scope | Flutter destination | REST contract | Automated evidence | Limitation |
|---|---|---|---|---|
| Auth/onboarding/account | auth flow, family selector, Account | `/auth/*`, `/profile/*`, `/families` | auth/account tests | Device deep links pending |
| Dashboard/family administration | `/`, `/family/manage` | family dashboard/assets/roles/branches | dashboard/family tests | None known |
| Members/relationships | `/members`, `/relationships`, `/relationship-resolver` | members, base relationships, resolver | Phase 4 + backend tests | None known |
| Family tree/export | `/tree` | generate, PNG/PDF jobs | Phase 5 + backend tests | Device share/storage pending |
| Cross-family link | `/family-links`, `/family-links/:uuid/tree` | family-link request/approve/revoke/partner-tree | `family_link_phase_test`, `FamilyLinkApiTest` | Device partner-tree share pending |
| Pre-marriage consent view | Web-only by design | web routes, no REST/mobile task yet | `WebFamilyViewTest`, scoped-view unit tests | Mobile parity intentionally deferred |
| Articles/photos/events | `/articles`, `/photos`, `/events` | content/media/event endpoints | Phase 6 + backend tests | Camera permission device test pending |
| Timeline/notifications | `/activity`, `/account/notifications` | timeline/notifications/push devices | Phase 6 + backend tests | Production push pending |
| Search/reports/gamification | `/search`, `/reports`, `/gamification` | search/report/gamification endpoints | Phase 7 + backend tests | None known |
| Super-admin | Web-only | `/admin/*` | administration tests | Intentionally out of scope |

No known in-scope web capability lacks a Flutter route/approved REST contract, except the
pre-marriage view which is explicitly web-first. End-to-end role/device acceptance is still
required, so Phase 9 and parity remain open.

## Reproducible release gates

Pull-request CI:

```bash
composer test && composer analyse && composer pint --test && npm run build
cd mobile && flutter analyze && flutter test --coverage
```

Manual/tag release workflow (`.github/workflows/release-gate.yml`):

```bash
# Backend: Pint, PHPStan, tests, 80% global + 95% engine coverage gate.
composer test:coverage && composer test:coverage:enforce

# Mobile: analysis, tests, 80% Flutter coverage gate, unsigned release builds.
python3 scripts/check-flutter-coverage.py \
  --lcov mobile/coverage/lcov.info --min 80 --exclude-prefix lib/l10n/
cd mobile
flutter build appbundle --release --flavor production \
  --dart-define=APP_FLAVOR=production \
  --dart-define=API_BASE_URL=https://api.familytree.id/api/v1
flutter build ios --release --no-codesign \
  --dart-define=APP_FLAVOR=production \
  --dart-define=API_BASE_URL=https://api.familytree.id/api/v1
```

Do not commit signing files, Firebase configuration, API credentials, provisioning profiles, or
store private keys.

## Release completion checklist (external)

- [ ] Flutter coverage >=80% and release-gate workflow green.
- [ ] Real-device Android/iOS install, upgrade, deep link, push, and permission tests.
- [ ] Accessibility/screen-reader and profile-mode performance sign-off.
- [ ] Production signing/provisioning + Firebase files injected by CI secrets.
- [ ] `assetlinks.json` and AASA published for `familytree.id`.
- [ ] Store listings, screenshots, privacy policy URL, and data-safety answers approved.
- [ ] Owner/admin/member/outsider role acceptance recorded in the parity matrix.
