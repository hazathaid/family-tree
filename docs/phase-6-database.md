# Phase 6 Database Documentation

Phase 6 introduces categories, articles, comments, and likes for FT-601 through FT-605. All public entities use UUIDs, foreign keys, indexes, and timestamps.

## `article_categories`

| Field | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key |
| `uuid` | UUID | Unique public identifier |
| `name` | VARCHAR(100) | Unique category name |
| `slug` | VARCHAR(120) | Unique URL slug |
| `description` | TEXT | Nullable |
| `created_at` | TIMESTAMP | Audit field |
| `updated_at` | TIMESTAMP | Audit field |
| `deleted_at` | TIMESTAMP | Soft delete |

Categories are global catalog data. Initial seed values: Sejarah, Pengumuman, Cerita, and Memorial.

## `articles`

| Field | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key |
| `uuid` | UUID | Unique public identifier |
| `family_id` | BIGINT | FK to `families`, cascade delete |
| `author_id` | BIGINT | FK to `users`, restrict delete |
| `category_id` | BIGINT | FK to `article_categories`, restrict delete |
| `title` | VARCHAR(255) | Required |
| `slug` | VARCHAR(255) | URL slug, unique within family |
| `excerpt` | TEXT | Nullable generated summary |
| `content` | LONGTEXT | Sanitized rich-text HTML |
| `featured_image` | VARCHAR(255) | Nullable storage path |
| `status` | ENUM | `draft`, `published`, `archived` |
| `is_featured` | BOOLEAN | Default false |
| `featured_at` | TIMESTAMP | Nullable pin time |
| `published_at` | TIMESTAMP | Nullable publication time |
| `created_at` | TIMESTAMP | Audit field |
| `updated_at` | TIMESTAMP | Audit field |
| `deleted_at` | TIMESTAMP | Soft delete |

Indexes:

```text
uuid UNIQUE
family_id, slug UNIQUE
family_id, status, published_at
family_id, is_featured, featured_at
category_id
author_id
```

## `article_comments`

| Field | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key |
| `uuid` | UUID | Unique public identifier |
| `article_id` | BIGINT | FK to `articles`, cascade delete |
| `user_id` | BIGINT | FK to `users`, restrict delete |
| `comment` | TEXT | Plain text, required |
| `created_at` | TIMESTAMP | Audit field |
| `updated_at` | TIMESTAMP | Audit field |
| `deleted_at` | TIMESTAMP | Soft delete |

Indexes: `uuid`, `article_id, created_at`, and `user_id`.

## `article_likes`

| Field | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key |
| `uuid` | UUID | Unique public identifier |
| `article_id` | BIGINT | FK to `articles`, cascade delete |
| `user_id` | BIGINT | FK to `users`, cascade delete |
| `created_at` | TIMESTAMP | Audit field |
| `updated_at` | TIMESTAMP | Audit field |

Constraints and indexes:

```text
uuid UNIQUE
article_id, user_id UNIQUE
article_id
user_id
```

Likes do not use soft deletes. Unlike removes the row, while repeated like/unlike operations are idempotent at the service layer.

## Data Integrity Rules

* Article author must be a member of the selected family when the article is created.
* Category deletion is blocked while non-deleted articles reference it.
* `published_at` is set only when status becomes published.
* Unpublishing or archiving clears featured state.
* Only published articles may appear in featured queries.
* Comment and like users must have access to the article family.
* Rich-text content is stored only after server-side sanitization.
