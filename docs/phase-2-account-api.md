# Phase 2 Account API

Authenticated endpoints under `/api/v1/profile`:

| Method | Path | Purpose |
|---|---|---|
| GET/PUT | `/notification-preferences` | Read/update four required boolean notification preferences |
| GET | `/sessions` | List the caller's Sanctum device tokens using safe public fields |
| DELETE | `/sessions/{uuid}` | Revoke one token owned by the caller and report whether it was current |

All responses use the standard success envelope. Validation uses Form Requests. Account policy requires actor and target user identity. Unknown or foreign session UUIDs return 404 without disclosing ownership. Session resources omit numeric ID, bearer/hash value, IP, and payload.
