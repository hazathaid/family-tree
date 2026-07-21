# Phase 8 Database - Timeline

## `activity_logs`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key. |
| `uuid` | UUID | Unique public identifier. |
| `family_id` | BIGINT | Family foreign key; cascade on family deletion. |
| `user_id` | BIGINT nullable | Actor foreign key; null when the user is deleted. |
| `activity_type` | VARCHAR(100) | Machine-readable activity type. |
| `payload` | JSON | Snapshot needed to render the feed. |
| `created_at`, `updated_at` | TIMESTAMP | Laravel timestamps. |

Indexes cover UUID, family chronology, and family/type chronology. Supported Phase 8 types are `MEMBER_CREATED`, `ARTICLE_CREATED`, `PHOTO_UPLOADED`, and `EVENT_CREATED`.
