# Phase 2 Mobile Authentication and Account

Status: complete (2026-07-22). Tasks: FT-MOB-101–104 and FT-API-101. Phase 3 was not implemented.

## Mobile flows

- Authentication includes login, registration, neutral forgot-password feedback, signed-link reset, session bootstrap, logout, suspended/credential/validation/rate-limit/offline error mapping, and password visibility.
- Verification includes notice, 60-second resend cooldown, signed-link completion, authoritative user refresh, and intended-route continuation through router guards.
- Onboarding handles zero, one, and multiple families; supports family creation and selection. The active family UUID is persisted per user in bounded non-sensitive Hive storage and revalidated against `/families` at startup.
- Account includes profile update, 5 MB avatar selection/upload, notification preferences, current-password protected password change, safe session list, and session revoke. Revoking the active token or changing password ends the local session.
- User/family providers and scoped cache are cleared or invalidated on logout and family switch. Laravel remains authoritative for all validation and authorization.

## Accessibility and state behavior

Forms are scrollable and width-bounded for phone/tablet layouts, support 200% text scale, provide labeled password visibility controls, inline API validation, disabled mutation buttons, retry states, 48dp list targets, and generic safe failures.

## Verification

Flutter model/widget coverage includes API parsing, large-text registration layout, and invalid reset-link state. Laravel feature coverage includes preference validation/defaults, safe session serialization, cross-user revoke denial, current-token revoke, existing profile/password/avatar flows, and authentication flows.
