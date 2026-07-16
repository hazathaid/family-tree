# Phase 6 Service Documentation

Phase 6 covers FT-601 through FT-605. It follows Repository Pattern, Service Layer Pattern, Form Requests, API Resources, Policies, DTOs, events, and thin controllers.

## FT-601 Categories

### `ArticleCategoryRepositoryInterface`

Handles category pagination, UUID lookup, uniqueness checks, persistence, and active-article usage checks.

### `ArticleCategoryService`

Responsibilities:

* Create slugs from category names.
* Enforce unique names and slugs.
* Create, update, and soft-delete categories.
* Reject deletion when active articles use the category.

Category mutation is authorized through `ArticleCategoryPolicy` and reserved for platform administrators.

## FT-602 Article CRUD and Publishing

### `ArticleRepositoryInterface`

Responsibilities:

* UUID lookup with family, author, category, likes, and comments as needed.
* User-scoped, paginated filtering by family, category, status, featured state, and search text.
* Article persistence without embedding business rules.
* Efficient counter loading through `withCount` rather than N+1 queries.

### `ArticleData`

DTO containing family, category, title, sanitized content, excerpt, status, and optional featured-image path.

### `ArticleService`

Responsibilities:

* Verify family membership and category validity.
* Generate a family-unique slug from the title.
* Sanitize rich-text HTML using a central allowlist.
* Create drafts and update authorized articles.
* Soft-delete articles and remove their stored cover images when appropriate.
* Publish articles transactionally and set `published_at`.
* Archive articles and remove featured state.
* Upload, validate, crop/resize, and replace 1200x630 featured images.
* Write required activity and audit logs when their tables are available.

Publishing dispatches `ArticlePublished`. The future timeline listener can consume that event without moving publishing logic into the controller.

### HTTP Components

* `ArticleController`
* `StoreArticleRequest`
* `UpdateArticleRequest`
* `UploadArticleImageRequest`
* `ArticleResource`
* `ArticlePolicy`

The controller authorizes, passes validated DTO data to the service, and returns resources only.

## FT-603 Comments

### `ArticleCommentRepositoryInterface`

Handles article-scoped pagination, UUID lookup, creation, updates, and soft deletion.

### `ArticleCommentService`

Responsibilities:

* Accept comments only for published articles.
* Verify that the user belongs to the article family.
* Store comments as escaped plain text.
* Allow owners to edit or delete their comments.
* Allow family admins to delete comments for moderation.
* Ensure nested comment routes cannot reference a comment from another article.

HTTP components: `ArticleCommentController`, `StoreArticleCommentRequest`, `UpdateArticleCommentRequest`, `ArticleCommentResource`, and `ArticleCommentPolicy`.

## FT-604 Likes

### `ArticleLikeRepositoryInterface`

Provides existence checks, idempotent creation, deletion, and article like counts using the unique article-user constraint.

### `ArticleLikeService`

Responsibilities:

* Permit likes only on published articles.
* Verify family membership.
* Make like and unlike idempotent.
* Handle concurrent duplicate likes safely through the database constraint.
* Return updated `likes_count` and user-like state.

HTTP component: `ArticleLikeController`. No Form Request is required because the POST carries no body; route models and policy authorization provide input validation.

## FT-605 Featured Articles

### `FeaturedArticleService`

Responsibilities:

* Feature only published articles.
* Require family admin or owner permission.
* Set `is_featured` and `featured_at` transactionally.
* Make feature and unfeature idempotent.
* Return published featured articles ordered by `featured_at` descending.
* Automatically remove featured state when an article is archived or unpublished.

HTTP component: `FeaturedArticleController`. Featured listing uses `ArticleResource`.

## Security and Validation

* Rich text is sanitized server-side before persistence and escaped safely in Blade output.
* Article images accept only jpg, jpeg, png, and webp, maximum 10 MB.
* Policies enforce family isolation for every article, comment, like, and featured action.
* Public IDs use UUIDs; numeric database IDs are never required by clients.
* API errors never expose storage paths or internal exceptions.
* Mutation endpoints use rate limiting appropriate to articles, comments, likes, and uploads.

## Required Tests

Unit tests:

* Category CRUD, uniqueness, and deletion protection.
* Article slug collision handling and rich-text sanitization.
* Draft visibility and publish state transition.
* Featured-image validation, resizing, and replacement.
* Comment ownership, moderation, and cross-article rejection.
* Idempotent likes and concurrent duplicate protection.
* Feature/unfeature rules and published-only featured listing.

Feature tests:

* Category and article CRUD authorization.
* Author, family admin, family member, and outsider access matrix.
* Draft, published, archived, and featured filtering.
* Comment add/edit/delete endpoints.
* Like/unlike endpoints and returned counters.
* Featured endpoint ordering.
* Standard success and validation error envelopes.

Test coverage must remain at least 80%. Before completion, `composer test`, `composer analyse`, and `composer pint` must pass.
