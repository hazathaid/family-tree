# Phase 6 API Documentation

Phase 6 covers the implemented FT-601 through FT-605 article endpoints.

Base URL:

```text
/api/v1
```

All endpoints require Sanctum bearer authentication and use UUID route parameters. Responses use the standard project envelope.

## FT-601 Article Categories

### List Categories

```http
GET /article-categories
```

Available to authenticated users. Supports `search`, `page`, and `limit`.

### Create Category

```http
POST /article-categories
```

```json
{
  "name": "Sejarah",
  "description": "Dokumentasi sejarah keluarga"
}
```

### View, Update, and Delete Category

```http
GET    /article-categories/{category_uuid}
PUT    /article-categories/{category_uuid}
DELETE /article-categories/{category_uuid}
```

Category mutation requires platform administration permission. Deletion is rejected while active articles use the category.

## FT-602 Articles

### List Articles

```http
GET /articles
```

Filters:

```text
family_uuid
category_uuid
status=draft|published|archived
featured=true|false
search
page
limit
```

Family members see published articles plus their own drafts. Family admins see every article in their family.

### Create Article

```http
POST /articles
```

```json
{
  "family_uuid": "family-public-uuid",
  "category_uuid": "category-public-uuid",
  "title": "Sejarah Keluarga",
  "content": "<p>Isi artikel yang sudah disanitasi.</p>",
  "status": "draft"
}
```

`content` accepts sanitized rich-text HTML. Allowed tags and attributes must be configured centrally; scripts, inline event handlers, iframes, and unsafe URLs are rejected or removed.

### View, Update, and Delete Article

```http
GET    /articles/{article_uuid}
PUT    /articles/{article_uuid}
DELETE /articles/{article_uuid}
```

Authors may update and delete their own drafts. Family admins may moderate articles in their family. Drafts are not visible to other regular members.

### Publish Article

```http
POST /articles/{article_uuid}/publish
```

Publishing changes status to `published`, records `published_at`, and dispatches the article-published domain event. Repeating the request is idempotent.

### Upload Featured Image

```http
POST /articles/{article_uuid}/featured-image
```

Multipart field: `image`.

Allowed types: `jpg`, `jpeg`, `png`, `webp`. Maximum size: 10 MB. The image is normalized to the project article-cover standard of 1200x630.

## FT-603 Comments

### List and Add Comments

```http
GET  /articles/{article_uuid}/comments
POST /articles/{article_uuid}/comments
```

```json
{
  "comment": "Artikel yang sangat menarik."
}
```

Only published articles accept comments. Listing is paginated and ordered oldest first.

### Update and Delete Comment

```http
PUT    /articles/{article_uuid}/comments/{comment_uuid}
DELETE /articles/{article_uuid}/comments/{comment_uuid}
```

Comment owners can edit or delete their comments. Family admins can delete comments for moderation. A comment UUID must belong to the article UUID in the route.

## FT-604 Likes

```http
POST   /articles/{article_uuid}/like
DELETE /articles/{article_uuid}/like
```

Like and unlike operations are idempotent. Only published articles can be liked. The article resource returns `likes_count` and `is_liked_by_me`.

## FT-605 Featured Articles

```http
POST   /articles/{article_uuid}/feature
DELETE /articles/{article_uuid}/feature
GET    /families/{family_uuid}/articles/featured
```

Feature and unfeature operations require family admin or owner permission. Featured results include only published, non-deleted articles and are ordered by most recently pinned.

## Article Resource

```json
{
  "uuid": "article-public-uuid",
  "family_uuid": "family-public-uuid",
  "category": {"uuid": "category-public-uuid", "name": "Sejarah"},
  "author": {"uuid": "user-public-uuid", "name": "Ahmad"},
  "title": "Sejarah Keluarga",
  "slug": "sejarah-keluarga",
  "content": "<p>Isi artikel.</p>",
  "featured_image_url": null,
  "status": "published",
  "is_featured": true,
  "published_at": "2026-07-16T10:00:00Z",
  "likes_count": 12,
  "comments_count": 3,
  "is_liked_by_me": true
}
```

## Validation and Errors

Validation errors return HTTP 422. Unauthorized family access returns HTTP 403, missing resources return HTTP 404, and duplicate operations remain idempotent. Internal exceptions are never exposed.
