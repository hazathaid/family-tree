# Phase 2 Account Database

Migration `2026_07_22_000000_add_uuid_to_personal_access_tokens_table` adds a nullable-while-backfilling, unique UUID to Sanctum personal access tokens. Existing rows are backfilled and new rows receive UUIDs through the custom `PersonalAccessToken` model. The UUID is the only public revoke identifier; token hashes and numeric keys remain internal.

Notification preferences continue to use `users.notification_preferences` JSON; no derived or family relationship data is introduced.
