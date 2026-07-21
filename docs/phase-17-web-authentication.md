# Phase 17 Step 2 — Web Authentication and Onboarding

## Routes

Guest routes provide login, registration, forgot-password, and reset-password forms. Authenticated routes provide logout and email verification. Verified users can open onboarding, create a family, select an accessible family, and enter the dashboard.

## Services and security

`AuthService` owns credential checks, session login/logout, account status checks, and login timestamps. `PasswordResetService` and `EmailVerificationService` are reused by the web controllers. `WebOnboardingService` resolves accessible families and stores the selected public family UUID in `active_family_uuid` in the session.

All state-changing forms use Form Requests and CSRF tokens. Family creation and selection use `FamilyPolicy`; users cannot activate a family they cannot view. Password reset invalidates API tokens, and authentication errors are returned as validation messages without internal exception details.

## Redirect behavior

- Guests who request authenticated pages are redirected to login.
- Authenticated users who request guest pages are redirected to the dashboard.
- Unverified users are directed to the email verification notice.
- A verified user with one family enters the dashboard automatically.
- A verified user with zero or multiple families enters family selection.
