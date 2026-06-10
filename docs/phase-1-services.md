# Phase 1 Service Documentation

Phase 1 follows the Repository Pattern and Service Layer Pattern.

## Repository

### `UserRepositoryInterface`

Methods:

```text
create(array $attributes): User
update(User $user, array $attributes): User
findByEmail(string $email): ?User
findByUuid(string $uuid): ?User
paginate(int $perPage = 15): LengthAwarePaginator
```

### `EloquentUserRepository`

Eloquent implementation bound in `AppServiceProvider`.

## Services

### `AuthService`

Responsibilities:

* Register users.
* Send the Laravel registered event for email verification.
* Authenticate active users.
* Issue Sanctum tokens.
* Update `last_login_at`.
* Revoke the current token on logout.

### `PasswordResetService`

Responsibilities:

* Send password reset links through Laravel's password broker.
* Reset passwords.
* Revoke existing tokens after password reset.

### `EmailVerificationService`

Responsibilities:

* Send verification links.
* Mark user emails as verified through Laravel's signed verification request.

### `ProfileService`

Responsibilities:

* Update profile fields.
* Clear email verification when the email changes.
* Change password and revoke tokens.
* Store avatar images on the configured public disk.

## Controllers

Controllers only validate requests, call services, and return API resources inside the project response envelope.

## Validation

All mutating endpoints use Form Request classes:

* `RegisterRequest`
* `LoginRequest`
* `ForgotPasswordRequest`
* `ResetPasswordRequest`
* `UpdateProfileRequest`
* `ChangePasswordRequest`
* `UploadAvatarRequest`
