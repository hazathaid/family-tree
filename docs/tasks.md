# Family Tree Platform Tasks

## Purpose

Dokumen ini adalah backlog eksekusi proyek. Implementasi harus dilakukan satu task pada satu waktu dan tidak boleh melompat ke task berikutnya sebelum acceptance criteria task aktif terpenuhi.

Fokus backlog saat ini adalah menyamakan kapabilitas aplikasi Flutter dengan web Phase 17, tanpa memindahkan business logic dari Laravel ke Flutter.

## Mandatory Reading Gate

Sebelum mengimplementasikan task apa pun, baca dokumen berikut secara lengkap:

1. `docs/prd.md`
2. `docs/database-schema.md`
3. `docs/api-spec.md`
4. `docs/tech-spec.md`
5. `docs/ui-spec.md`
6. `docs/business-rules.md`
7. `docs/family-relationship-engine.md`
8. `docs/tree-generation-engine.md`
9. `docs/tasks.md`

Jika satu dokumen tidak relevan dengan task, dokumen tetap harus diperiksa untuk memastikan tidak ada aturan lintas fitur. Dokumen engine wajib dibaca untuk semua task yang menyentuh anggota, relationship, pencarian generasi, laporan generasi, atau family tree.

> Status audit 2026-07-22: seluruh dokumen mandatory reading telah dipulihkan melalui FT-DOC-001 sampai FT-DOC-007 dan dikonsolidasikan terhadap source code aktual. Gap yang ditemukan dicatat di masing-masing spesifikasi dan harus ditutup oleh task API/mobile pemiliknya sebelum kapabilitas terkait diimplementasikan.

## Global Rules

- Kerjakan hanya task yang diminta secara eksplisit.
- Flutter hanya menangani presentasi, input, state UI, penyimpanan sesi, upload/download, dan notifikasi perangkat.
- Validasi otoritatif, authorization, relationship calculation, tree generation, sanitasi, serta business rules tetap di Laravel.
- Semua komunikasi Flutter menggunakan REST API `/api/v1` dan Sanctum Bearer token.
- Jangan menyimpan relationship turunan di mobile atau database.
- Gunakan UUID sebagai identifier publik.
- Setiap mutation harus mempunyai loading, success, validation error, authorization error, empty, offline, dan retry state yang sesuai.
- Pagination, filter, dan pencarian harus dilakukan server-side untuk dataset besar.
- Setiap task wajib memperbarui dokumentasi API/service/database bila kontraknya berubah.
- Jangan menganggap dokumentasi fase sebagai bukti completion; acceptance criteria dan quality gate tetap harus dijalankan.

## Definition of Done

Sebuah task hanya complete bila seluruh poin yang relevan terpenuhi:

- Acceptance criteria task terpenuhi.
- Unit dan widget test Flutter ditambahkan.
- Integration test ditambahkan untuk alur API kritis.
- Test Laravel Feature/Unit ditambahkan jika API berubah.
- Tidak ada business logic domain yang diduplikasi di Flutter.
- Authorization diuji untuk owner, admin, member, outsider, dan super-admin bila relevan.
- UI responsif di ponsel kecil, ponsel besar, dan tablet.
- Accessibility mencakup semantic label, focus order, text scaling, dan minimum touch target.
- Error internal tidak ditampilkan kepada pengguna.
- Dokumentasi terkait diperbarui.
- Quality gate berhasil:

```bash
composer test
composer analyse
composer pint
npm run build
cd mobile && flutter analyze
cd mobile && flutter test
```

Untuk task release, Android dan iOS build juga wajib berhasil.

---

# Phase 0 — Specification Recovery

Semua task fase ini adalah blocker. Jangan memulai implementasi parity sebelum FT-DOC-001 sampai FT-DOC-007 selesai dan konsisten dengan source code yang sudah ada.

## FT-DOC-001 — Product Requirements ✅ Complete (2026-07-22)

**Output:** `docs/prd.md`

- Definisikan persona, role, use case, scope web/mobile, non-goals, dan success metrics.
- Tentukan apakah mobile harus mencakup console super-admin atau hanya pengalaman anggota keluarga.
- Tetapkan parity sebagai kesetaraan kapabilitas, bukan penyalinan layout web.
- Petakan requirement ke task ID di dokumen ini.

## FT-DOC-002 — Consolidated Database Schema ✅ Complete (2026-07-22)

**Output:** `docs/database-schema.md`

- Konsolidasikan dokumentasi database Phase 1–16 dan migration aktual.
- Dokumentasikan UUID, foreign key, index, soft delete, cache table, activity log, dan audit log.
- Tandai perbedaan antara dokumentasi dan migration sebagai issue, bukan mengubah schema diam-diam.

## FT-DOC-003 — Consolidated API Specification ✅ Complete (2026-07-22)

**Output:** `docs/api-spec.md`

- Konsolidasikan seluruh endpoint aktual di `routes/api.php` dan dokumentasi API Phase 1–16.
- Dokumentasikan request, response, pagination metadata, upload, error envelope, RBAC, rate limit, dan binary download.
- Tandai endpoint web yang belum mempunyai padanan REST.
- Hasil akhir menjadi kontrak tunggal untuk Flutter.

## FT-DOC-004 — Technical Specification ✅ Complete (2026-07-22)

**Output:** `docs/tech-spec.md`

- Dokumentasikan arsitektur Laravel dan Flutter, dependency direction, environment, flavor, serta CI/CD.
- Tetapkan struktur Flutter berbasis feature, repository abstraction, immutable model, routing, state management, dan secure token storage.
- Definisikan retry, timeout, refresh token/session-expiry behavior, logging aman, deep link, Firebase, upload, dan download.

## FT-DOC-005 — UI Specification ✅ Complete (2026-07-22)

**Output:** `docs/ui-spec.md`

- Konsolidasikan design tokens dari web Phase 17.
- Definisikan mobile navigation, screen inventory, reusable components, responsive breakpoints, dark mode decision, empty/loading/error states, dan accessibility.
- Sediakan mapping setiap halaman web ke screen atau flow Flutter.

## FT-DOC-006 — Business Rules ✅ Complete (2026-07-22)

**Output:** `docs/business-rules.md`

- Konsolidasikan RBAC, family isolation, membership, privacy, upload limits, publication, moderation, RSVP, notification, dan gamification rules.
- Business rules harus menunjuk ke service/policy Laravel yang menjadi sumber kebenaran.

## FT-DOC-007 — Relationship and Tree Specifications ✅ Complete (2026-07-22)

**Output:**

- `docs/family-relationship-engine.md`
- `docs/tree-generation-engine.md`

- Dokumentasikan BFS, base relationship yang boleh disimpan, derived relationship matrix, cycle handling, cache, dan invalidation.
- Dokumentasikan ancestor, descendant, full tree, depth, layout, lazy expansion, export, dan performance boundary.
- Selaraskan spesifikasi dengan implementasi dan test aktual sebelum task tree mobile dimulai.

---

# Phase 1 — Mobile Foundation

## FT-MOB-001 — Generate and Commit Platform Runners ✅ Complete (2026-07-22)

**Depends on:** FT-DOC-001 sampai FT-DOC-007

- Generate runner Android dan iOS dari project Flutter yang sudah ada.
- Tetapkan application ID/bundle ID, minimum OS, orientation, app name, icon placeholder, dan signing strategy.
- Commit `pubspec.lock`; jangan commit secret atau file signing.
- Dokumentasikan setup Firebase per environment.

**Acceptance criteria:** project dapat dijalankan di Android emulator dan iOS simulator tanpa langkah `flutter create` lagi.

## FT-MOB-002 — Environment and Flavor Configuration ✅ Complete (2026-07-22)

**Depends on:** FT-MOB-001

- Tambahkan development, staging, dan production configuration.
- Validasi `API_BASE_URL`; production tidak boleh memakai clear-text HTTP.
- Pisahkan Firebase configuration dan application identifier per flavor.
- Tambahkan halaman diagnostics yang hanya aktif di debug build.

## FT-MOB-003 — Core Architecture Refactor ✅ Complete (2026-07-22)

**Depends on:** FT-MOB-002

- Susun folder berbasis feature dengan layer presentation, application, domain contract, dan data.
- Pertahankan Riverpod, tetapi hilangkan state global ad-hoc dan dependency yang sulit dites.
- Tambahkan typed API result, pagination model, API error mapping, timeout, cancellation, dan bounded retry.
- Gunakan secure storage untuk token; Hive hanya untuk cache non-sensitif.

## FT-MOB-004 — Mobile Design System ✅ Complete (2026-07-22)

**Depends on:** FT-MOB-003

- Implementasikan color, typography, spacing, radius, elevation, icon, button, form, card, badge, dialog, snackbar, skeleton, empty state, dan error state sesuai `ui-spec.md`.
- Dukung text scaling dan tablet layout.
- Tambahkan widget tests dan golden tests untuk komponen utama.

## FT-MOB-005 — Routing, Navigation, and Deep Links ✅ Complete (2026-07-22)

**Depends on:** FT-MOB-003, FT-MOB-004

- Tambahkan declarative router dengan auth, verification, onboarding, family-selection, member, article, event, notification, dan admin guards.
- Restore intended destination setelah login/verifikasi.
- Implementasikan deep link untuk email verification, reset password, notification target, article, event, dan member.

## FT-MOB-006 — Offline, Cache, and Connectivity Policy ✅ Complete (2026-07-22)

**Depends on:** FT-MOB-003

- Cache bounded untuk data read-only yang aman.
- Tampilkan stale-data indicator dan explicit retry.
- Jangan queue mutation berisiko secara otomatis.
- Bersihkan seluruh cache keluarga dan token pada logout/account switch.

---

# Phase 2 — Authentication, Onboarding, and Account

## FT-MOB-101 — Authentication Parity

**API:** register, login, logout, me, forgot/reset password

- Tambahkan register, forgot password, reset password, session bootstrap, dan logout.
- Tangani suspended account, invalid credential, rate limit, offline, dan expired token.
- Tambahkan password visibility dan validation feedback dari API.

## FT-MOB-102 — Email Verification and Deep-Link Completion

- Tambahkan verification notice, resend cooldown, dan signed-link handling.
- Setelah sukses, refresh user lalu teruskan onboarding/deep link tujuan.

## FT-MOB-103 — Family Onboarding and Active Family

- Implementasikan zero/one/multiple family flow setara web.
- Tambahkan create family dan family selector.
- Persist UUID keluarga aktif per user, validasi ulang ke API saat startup.
- Account/family switch harus menginvalidasi provider dan cache scoped.

## FT-API-101 — Account Preferences and Session API Gap

**Required before:** FT-MOB-104

- Tambahkan REST API untuk notification preferences dan daftar/revoke session bila telah disetujui PRD.
- Gunakan service, repository, Form Request, Resource, Policy, dan test.
- Jangan mengekspos session ID, token, IP detail sensitif, atau exception internal.

## FT-MOB-104 — Profile, Avatar, Preferences, and Security

**Depends on:** FT-API-101

- View/update profile, avatar upload, notification preferences, change password, session list, dan revoke session.
- Email/password change meminta current password sesuai business rules.
- Re-authenticate atau logout ketika API mencabut token aktif.

---

# Phase 3 — Dashboard and Family Management

## FT-API-201 — Rich Mobile Dashboard Contract

**Required before:** FT-MOB-201

- Putuskan apakah dashboard mobile memakai endpoint agregat baru atau endpoint domain terpisah.
- Sediakan statistik, activity, birthday, upcoming event, notification summary, family facts, dan recent members dengan query bounded.
- Pertahankan target respons dashboard kurang dari dua detik dan cache isolation per keluarga/user.

## FT-MOB-201 — Dashboard Parity

**Depends on:** FT-API-201

- Implementasikan welcome banner, enam statistik, activity feed, birthday, upcoming event, notification summary, family facts, dan recent members.
- Semua widget mempunyai loading, empty, error, pull-to-refresh, dan target-navigation state.

## FT-API-202 — Family Settings API Gap Audit and Closure

**Required before:** FT-MOB-202

- Audit update family untuk logo, cover, privacy, dan notification setting terhadap web.
- Tambahkan hanya kontrak yang disetujui PRD dan belum tersedia.
- Terapkan upload validation, Policy, logging, Resource, dan test isolation.

## FT-MOB-202 — Family Settings

**Depends on:** FT-API-202

- Tampilkan/update family identity, logo, cover, privacy, dan setting yang didukung API.
- Owner/admin-only controls harus disembunyikan dan tetap ditolak server jika dipanggil langsung.

## FT-MOB-203 — Branch Management

- List, create, update, dan delete family branches.
- Tangani branch in-use sesuai error business rule dari API.

## FT-MOB-204 — Family Access and Role Management

- List memberships, invite by email, assign role, dan remove membership.
- Konfirmasi destructive action dan tampilkan aturan last-owner.

---

# Phase 4 — Members and Relationships

## FT-MOB-301 — Member Directory

- Server-side pagination, search, gender, living status, branch, dan sort.
- Sediakan table-friendly tablet layout dan card layout untuk ponsel.
- Jangan memuat seluruh family member ke memory.

## FT-MOB-302 — Member Detail

- Tampilkan basic info, family info, biography, photo, base relationships, dan related-content states.
- Gunakan memorial marker yang konsisten untuk anggota meninggal.

## FT-MOB-303 — Member Create, Edit, Photo, and Delete

- Form create/edit sesuai API, upload/replace photo, dan soft-delete confirmation.
- Tampilkan field kematian secara kondisional tetapi biarkan API menjadi validator otoritatif.

## FT-MOB-304 — Base Relationship Management

- List/create/update/delete hanya `father`, `mother`, `child`, `husband`, dan `wife`.
- Member picker harus paginated dan scoped ke keluarga aktif.
- Jangan menyediakan input untuk Pakde, Bude, Om, Tante, Sepupu, Keponakan, Menantu, Mertua, atau relationship turunan lain.

## FT-MOB-305 — Relationship Resolver

- Pilih source dan target member lalu tampilkan hasil relationship dan path dari API.
- Tidak boleh menghitung relationship di Dart.
- Tangani disconnected members, cycle-safe response, dan cache invalidation melalui server.

---

# Phase 5 — Interactive Family Tree

## FT-API-301 — Tree Contract Alignment

**Required before:** FT-MOB-401

- Selaraskan `api-spec.md`, API aktual, web viewer, dan `tree-generation-engine.md` untuk layout `vertical`, `horizontal`, `radial`, dan/atau `compact`.
- Dokumentasikan lazy slice/expand contract dan relationship-to-root label.
- Pastikan UUID digunakan secara konsisten pada relationship-engine request.

## FT-MOB-401 — Tree Viewer Parity

**Depends on:** FT-API-301

- Support ancestor, descendant, full, root selection, depth 1–20, dan layout yang disahkan spec.
- Tambahkan pan, zoom, center, search/focus, expand/collapse, filter, dan detail sheet.
- Render graph dari API; jangan melakukan traversal domain di Flutter.

## FT-MOB-402 — Large Tree Performance and Accessibility

- Lazy-load slice, batasi object/widget aktif, dan ukur jank/memory.
- Target generation tetap kurang dari lima detik; UI harus tetap responsif untuk segment tree besar.
- Sediakan semantic alternative berupa daftar anggota/relationship untuk graph visual.

## FT-MOB-403 — Tree PNG/PDF Export

- Download/preview/share PNG dan PDF dari endpoint binary.
- Tangani permission, progress, cancellation, expired authentication, rate limit, dan storage failure.

---

# Phase 6 — Family Content and Engagement

## FT-MOB-501 — Article Categories and Article List

- List/filter/search/paginate article dan featured articles.
- Hormati draft visibility, author, dan family scope dari API.

## FT-MOB-502 — Article Detail and Engagement

- Render sanitized rich text, comments, like/unlike, comment edit/delete, dan moderation controls.
- Jangan mengeksekusi script atau URL tidak aman dari article HTML.

## FT-MOB-503 — Article Editor and Publishing

- Create/edit/delete draft, featured image upload, publish, feature/unfeature sesuai role.
- Gunakan editor yang menghasilkan subset HTML yang didukung sanitizer server.

## FT-MOB-504 — Photo Albums and Gallery

- List/create/view/update/delete albums dan paginated gallery.
- Album deletion confirmation menjelaskan bahwa foto dipertahankan bila aturan API demikian.

## FT-MOB-505 — Photo Upload, Detail, and Member Tags

- Image picker/camera, compression preview, upload progress, caption, capture date, album, dan tags.
- Enforce UX limit 10 MB dan format yang didukung; API tetap validator final.

## FT-MOB-506 — Events and RSVP

- Upcoming/all/search list, detail, attendee list, create/edit/delete, dan RSVP yes/no/maybe.
- Format waktu menggunakan timezone aplikasi dan tampilkan timezone secara jelas.

## FT-MOB-507 — Family Timeline

- Paginated timeline dengan filter members, photos, articles, dan events.
- Notification/deep-link target membuka resource terkait bila masih dapat diakses.

## FT-MOB-508 — Notifications and Push Completion

- Paginated read/unread list, mark one/all read, badge count, device registration/removal, foreground/background handling, dan deep links.
- Registrasi push dilakukan setelah login/permission dan dilepas saat logout.

---

# Phase 7 — Discovery, Reports, and Gamification

## FT-MOB-601 — Global and Advanced Search

- Grouped, paginated member/article/event results.
- Support name, city, living status, dan generation-relative-to-root filters.
- Generation calculation hanya berasal dari API.

## FT-API-401 — Report Contract Parity Audit

**Required before:** FT-MOB-602

- Audit kebutuhan web untuk statistics, generation, city, growth, activity trend, dan accessibility data table terhadap endpoint REST saat ini.
- Tambahkan response field/endpoint yang kurang dengan cache 15 menit dan family isolation.

## FT-MOB-602 — Reports and Insights

**Depends on:** FT-API-401

- Tampilkan statistik, generation distribution, city, growth, dan activity trend.
- Setiap chart mempunyai semantic/data-table alternative.
- Filter periode menggunakan timezone aplikasi.

## FT-MOB-603 — Gamification and Leaderboards

- Profile points, badges, family user leaderboard, dan family leaderboard.
- Gunakan server rank dan jangan menghitung ulang award/ranking di Flutter.

---

# Phase 8 — Administration

Fase ini hanya dikerjakan jika FT-DOC-001 menetapkan console super-admin sebagai bagian dari mobile parity.

## FT-MOB-701 — Admin Guard and Dashboard

- Super-admin-only navigation dan route guard.
- Non-admin tidak boleh melihat entry point; server tetap melakukan authorization final.

## FT-MOB-702 — User Administration

- Paginated user list/detail, status filter, suspend/activate, self-suspension prevention, dan confirmation.

## FT-MOB-703 — Family Moderation

- Family review dan targeted article/photo/event removal dengan explicit confirmation.
- Tampilkan audit reference setelah aksi berhasil.

## FT-MOB-704 — Audit Logs and CSV Export

- Filterable audit log list dan secure CSV download/share.
- Batasi render/list sesuai pagination dan jangan log payload sensitif di perangkat.

---

# Phase 9 — Quality, Security, and Release

## FT-MOB-801 — Automated Test Matrix

- Unit test untuk API parsing, repository, state transitions, cache, dan error mapping.
- Widget test untuk seluruh screen state dan RBAC visibility.
- Integration test untuk auth, onboarding, family switch, member CRUD, relationship, tree, article, photo, event, dan notification.
- Backend contract tests harus menjaga response yang digunakan Flutter.
- Tetapkan coverage minimum 80%; code relationship/tree Laravel tetap minimum 95%.

## FT-MOB-802 — Security and Privacy Review

- Audit secure storage, screenshots pada layar sensitif, logs, cache clearing, TLS, certificate policy, upload, deep link, WebView/rich text, dan token expiration.
- Verifikasi family isolation dan RBAC melalui negative integration tests.
- Jangan hardcode secret atau Firebase server credential di aplikasi.

## FT-MOB-803 — Performance and Reliability

- Profil startup, dashboard, scrolling, image memory, upload, search, dan tree rendering.
- Uji keluarga besar menggunakan fixture realistis dan paginated API.
- Tambahkan crash reporting dengan PII scrubbing dan opt-in/consent sesuai PRD.

## FT-MOB-804 — Accessibility and Localization

- Audit screen reader, keyboard/tablet navigation, contrast, text scaling, reduced motion, touch targets, dan semantic charts/tree alternative.
- Semua user-facing string masuk localization; Bahasa Indonesia menjadi locale utama.

## FT-MOB-805 — Android Release Readiness

- Production signing, app links, Firebase, notification channels, network security, permissions, icon/splash, versioning, release build, dan store metadata.
- Verifikasi clean install, upgrade, background push, dan deep link pada perangkat nyata.

## FT-MOB-806 — iOS Release Readiness

- Signing/capabilities, universal links, APNs/Firebase, privacy manifest, permissions, icon/splash, versioning, release build, dan store metadata.
- Verifikasi clean install, upgrade, background push, dan deep link pada perangkat nyata.

## FT-MOB-807 — End-to-End Parity Acceptance

- Jalankan setiap acceptance flow web dan mobile dengan role owner, admin, member, outsider, dan super-admin bila in scope.
- Buat parity matrix final yang menunjuk screen Flutter, endpoint API, automated test, dan known limitation.
- Tidak boleh menandai parity complete bila terdapat fitur web in-scope tanpa screen/flow Flutter atau tanpa API contract yang disahkan.

---

# Current Baseline

Audit repository pada 2026-07-22 menemukan baseline berikut:

- Flutter source tersedia di `mobile/`, tetapi runner Android/iOS dan `pubspec.lock` belum tersedia.
- Screen yang tersedia: login, dashboard dasar, tree dasar, dan notifications.
- Repository mobile baru memakai auth, families, dashboard, timeline, tree, notifications, dan push-device registration.
- Web Phase 17 mencakup authentication/onboarding, dashboard, family/member management, tree viewer, engagement, discovery/reports/account, serta administration.
- API Laravel tersedia lebih luas daripada client Flutter, tetapi beberapa kemampuan web masih perlu contract-gap audit.
- Test Flutter saat ini hanya mencakup app smoke/model parsing dan belum membuktikan parity.
- Status completion backend belum dapat dipastikan sampai seluruh quality gate berhasil dijalankan.

## Recommended Execution Order

Urutan aman adalah:

1. FT-DOC-001 sampai FT-DOC-007.
2. FT-MOB-001 sampai FT-MOB-006.
3. Kerjakan phase domain secara berurutan; selesaikan task `FT-API-*` sebelum screen yang bergantung padanya.
4. FT-MOB-801 sampai FT-MOB-807.

Jangan mengimplementasikan seluruh roadmap sekaligus. Contoh instruksi eksekusi yang benar:

```text
Implement FT-DOC-003 only
```

atau:

```text
Implement FT-MOB-001 only
```
