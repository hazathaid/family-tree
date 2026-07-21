# Phase 7 API Documentation

Phase 7 implements FT-701 through FT-703 under `/api/v1`. Every endpoint requires Sanctum authentication and returns the standard response envelope.

## Albums (FT-702)

```http
GET    /photo-albums?family_uuid={uuid}
POST   /photo-albums
GET    /photo-albums/{album_uuid}
PUT    /photo-albums/{album_uuid}
DELETE /photo-albums/{album_uuid}
```

Create payload: `family_uuid`, `name`, and optional `description`. Family members can view albums. The creator, family owner, or family admin can update and delete them. Deleting an album preserves its photos and clears their album assignment.

## Photos (FT-701)

```http
GET    /member-photos?family_uuid={uuid}&album_uuid={uuid}&member_uuid={uuid}
POST   /member-photos
GET    /member-photos/{photo_uuid}
DELETE /member-photos/{photo_uuid}
```

Upload uses multipart fields `family_uuid`, optional `album_uuid`, `image`, optional `caption`, and optional `captured_at`. Accepted formats are JPG, JPEG, PNG, and WebP up to 10 MB. Images are normalized to JPEG, resized to at most 2048px, compressed, and accompanied by a 400px thumbnail.

## Photo Tags (FT-703)

```http
PUT /member-photos/{photo_uuid}/tags
```

```json
{"member_uuids":["member-uuid"]}
```

The supplied list replaces existing tags. Every member must belong to the same family as the photo.
