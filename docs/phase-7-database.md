# Phase 7 Database Documentation

## `photo_albums`

Stores UUID-based family albums with `family_id`, `created_by`, `name`, `description`, timestamps, and soft deletes.

## `member_photos`

Stores UUID, family, optional album, uploader, private storage paths, original filename, MIME type, compressed size, dimensions, caption, capture time, timestamps, and soft deletes. Family and album chronology columns are indexed.

## `member_photo_tags`

Links photos to `family_members`. Its unique composite key prevents duplicate tags. Foreign keys cascade when a photo or family member is permanently removed.
