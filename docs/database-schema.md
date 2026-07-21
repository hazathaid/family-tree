# Consolidated Database Schema

Status: audited against all migrations present on 2026-07-22. Migrations are authoritative; this document does not change schema.

## Conventions

- MySQL 8.4 is used by Docker (project requirement is MySQL 8).
- Domain entities normally use bigint `id`, unique `uuid`, timestamps, indexed foreign keys, and soft deletes where restoration is meaningful.
- Public API identifiers are UUIDs; numeric IDs are internal.
- Foreign-key delete behavior is stated below. Family isolation is enforced in repositories/services/policies in addition to keys.
- Pivot/framework tables are exempt from a public UUID where noted.

## Domain tables

| Table | Important columns | Keys, indexes and lifecycle |
|---|---|---|
| `users` | uuid, name, email, phone, password, avatar, verification/login timestamps, status, notification_preferences | unique email/uuid; phone/status indexes; no soft delete |
| `families` | uuid, name, slug, description, origin_city, logo, cover_image, privacy, created_by | unique uuid/slug; creator FK restrict; soft delete |
| `family_user_roles` | uuid, family_id, user_id, role | unique family+user; cascade FKs; role index; timestamps |
| `family_branches` | uuid, family_id, name, description | unique family+name; cascade family; soft delete |
| `family_members` | uuid, family_id, branch_id, names/titles, gender, birth/death, is_alive, contact, biography, profile_photo, created_by, updated_by | family and search indexes; branch null-on-delete; actors null-on-delete; soft delete |
| `member_relationships` | uuid, family_id, source_member_id, target_member_id, relationship_type, dates, notes | base enum only; composite edge index; all graph FKs cascade; soft delete |
| `member_relationship_cache` | uuid, family/source/target IDs, relationship_name, relationship_path, is_connected, expires_at | unique lookup triple; expiry and FK indexes; cascade; no soft delete |
| `member_tree_cache` | uuid, family_id, member_id, mode, depth, tree_json, generated_at, expires_at | unique member+mode+depth; family+expiry index; cascade |
| `article_categories` | uuid, name, slug, description | globally unique name/slug; soft delete |
| `articles` | uuid, family/author/category IDs, title, slug, excerpt, content, image, status, feature/publish fields | unique family+slug; family publication/feature indexes; family cascade, author/category restrict; soft delete |
| `article_comments` | uuid, article_id, user_id, comment | article cascade, user restrict; soft delete |
| `article_likes` | uuid, article_id, user_id | unique article+user; cascades; timestamps |
| `photo_albums` | uuid, family_id, created_by, name, description | family cascade, creator restrict; soft delete |
| `member_photos` | uuid, family/album/uploader IDs, paths, file metadata, caption, captured_at | family cascade, album null, uploader restrict; soft delete |
| `member_photo_tags` | photo_id, family_member_id | unique pair; both cascade; timestamps; no UUID |
| `activity_logs` | uuid, family_id, user_id, activity_type, payload | family/time/type indexes; family cascade, user null; immutable operationally |
| `events` | uuid, family_id, title, description, date, location, organizer_id, reminder_sent_at | family/date and reminder indexes; family cascade, organizer restrict; soft delete |
| `event_attendees` | uuid, event_id, user_id, status | unique event+user; cascades |
| `notifications` | uuid, user_id, event_id, type, title, body, data, is_read, read_at | unique event+user; user/read/time index; event currently cascade |
| `push_device_tokens` | uuid, user_id, platform, token, is_active, last_used_at | unique token; user/platform active indexes; soft delete |
| `point_transactions` | uuid, family/user IDs, action, points, polymorphic source pair | unique action+source; family/user/time indexes; cascades |
| `badges` | uuid, code, name, description | unique uuid/code |
| `user_badges` | uuid, family/user/badge IDs, awarded_at | unique family+user+badge; cascades |
| `audit_logs` | uuid, user_id, action, auditable type/id/uuid, old/new values, IP, user agent | polymorphic and time/user indexes; user null; immutable operationally |

`member_relationships.relationship_type` is exactly `father`, `mother`, `child`, `husband`, or `wife`. Derived kinship is cache output, never a permanent graph edge.

## Framework and operations tables

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`, Spatie permission tables, and Telescope entry/tag/monitor tables follow vendor conventions. UUID is not required for these implementation tables. `personal_access_tokens` stores hashed Sanctum tokens; session/token values must never be returned as account-session identifiers.

## Cache semantics

- Relationship cache key is family+source+target, stores result/path/connectivity, and expires after 24 hours.
- Tree cache key is root member+mode+depth, stores serialized tree and expiry.
- Relationship/member mutations invalidate relationship and tree caches for the family through services.
- Cache tables are derived and may be rebuilt; they are not evidence of a permanent derived relationship.

## Actual-schema issues (do not silently migrate)

| ID | Difference / risk | Follow-up |
|---|---|---|
| DB-001 | Historical docs list `member_documents`; no migration/model/API exists. | Product/API task required before use. |
| DB-002 | Historical schema duplicated `family_members.uuid` and described gender/status values unlike the migration. Actual migration is authoritative. | Resolve in a future schema/business-rule task. |
| DB-003 | Not every entity/pivot/framework table has UUID or soft deletes, contrary to the old blanket statement. | Treat conventions as “domain/public entities where appropriate.” |
| DB-004 | `notifications` has unique `(event_id,user_id)` while non-event notifications use nullable event IDs; behavior depends on MySQL NULL uniqueness. | Review when notification expansion is implemented. |
| DB-005 | `point_transactions` unique key omits family/user, so the same source/action is globally unique. | Confirm intended idempotency before changes. |
| DB-006 | `article_categories` are global, not family scoped as some phase assumptions imply. | Keep API/policies aligned with actual model. |
| DB-007 | Tree cache uniqueness omits `family_id`, relying on globally unique member IDs. | Valid today; document if sharding/key strategy changes. |
| DB-008 | Docker pins MySQL 8.4 while high-level docs said 8.0. | Supported baseline is MySQL 8.x; test production exact version. |

## Migration policy

Schema changes require a new task, forward migration, model/repository/service/API and tests. Phase 0 records discrepancies only and does not edit migrations.
