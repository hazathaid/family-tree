# Phase 1 API Documentation

Base URL:

```text
/api/v1
```

All responses follow the project envelope:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Validation errors return:

```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {}
}
```

## Authentication

### Register

```http
POST /auth/register
```

Body:

```json
{
  "name": "Ahmad",
  "email": "ahmad@example.com",
  "phone": "08123456789",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Creates an active user with a UUID public identifier and sends an email verification notification.

### Login

```http
POST /auth/login
```

Body:

```json
{
  "email": "ahmad@example.com",
  "password": "secret123",
  "device_name": "mobile"
}
```

Returns a Sanctum bearer token and the authenticated user resource.

### Logout

```http
POST /auth/logout
Authorization: Bearer {token}
```

Revokes the current Sanctum token.

### Me

```http
GET /auth/me
Authorization: Bearer {token}
```

Returns the authenticated user profile.

### Forgot Password

```http
POST /auth/forgot-password
```

Body:

```json
{
  "email": "ahmad@example.com"
}
```

Sends a reset link using Laravel's password broker.

### Reset Password

```http
POST /auth/reset-password
```

Body:

```json
{
  "token": "reset-token",
  "email": "ahmad@example.com",
  "password": "new-secret",
  "password_confirmation": "new-secret"
}
```

Resets the password and revokes existing API tokens.

### Send Email Verification

```http
POST /auth/email/verification-notification
Authorization: Bearer {token}
```

Sends an email verification link when the user's email is not verified.

### Verify Email

```http
GET /auth/email/verify/{id}/{hash}
Authorization: Bearer {token}
```

Requires Laravel's signed URL parameters.

## Profile

### View Profile

```http
GET /profile
Authorization: Bearer {token}
```

### Update Profile

```http
PUT /profile
Authorization: Bearer {token}
```

Body:

```json
{
  "name": "Siti Aminah",
  "email": "siti@example.com",
  "phone": "081299988877"
}
```

Changing the email clears `email_verified_at`.

### Change Password

```http
PATCH /profile/password
Authorization: Bearer {token}
```

Body:

```json
{
  "current_password": "old-secret",
  "password": "new-secret",
  "password_confirmation": "new-secret"
}
```

Changes the password and revokes all user tokens.

### Upload Avatar

```http
POST /profile/avatar
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

Fields:

```text
avatar: jpg, jpeg, png, or webp image; max 5 MB
```
