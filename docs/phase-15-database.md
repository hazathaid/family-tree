# Phase 15 Database - Administration

## Existing entities

User management uses the indexed `users.status` enum (`active`, `suspended`). Family moderation uses existing soft deletes on `families`, `articles`, `member_photos`, and `events`.

## `audit_logs`

| Column | Purpose |
| --- | --- |
| `id`, `uuid` | Internal and public identifiers |
| `user_id` | Nullable FK to the acting user |
| `action` | Indexed action such as `user.suspended` |
| `auditable_type`, `auditable_id`, `auditable_uuid` | Subject identity |
| `old_values`, `new_values` | JSON change snapshots |
| `ip_address`, `user_agent` | Request context |
| timestamps | Creation and update times |

Indexes support subject lookup, action filtering, and chronological user lookup. User deletion preserves the audit record by setting `user_id` to null.
