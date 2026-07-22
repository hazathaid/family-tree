# Phase 2 Account Services

`AccountSessionService` lists and revokes authenticated-user Sanctum tokens through `AccountSessionRepositoryInterface`. It annotates the current token without exposing its internal key and returns whether a revoke ended the active session.

`ProfileService` remains authoritative for notification preference persistence, profile updates, avatar storage, and password changes. Password changes revoke all tokens. `AccountController` only adapts validated requests/resources and delegates behavior to these services.
