# Phase 8 Services - Timeline

## `ActivityLogService`

Creates timeline entries without placing business logic in controllers or models. Phase 8 integrates it with:

- `FamilyMemberService::create()` → `MEMBER_CREATED`
- `ArticleService::create()` → `ARTICLE_CREATED`
- `MemberPhotoService::upload()` → `PHOTO_UPLOADED`

`EVENT_CREATED` is reserved and filterable so Phase 9 can call `record()` when event creation is implemented.

## Timeline repository

`ActivityLogRepositoryInterface` isolates persistence and feed queries. Its Eloquent implementation restricts every result to a family membership, supports category and family filters, eager-loads actors/families, orders newest first, and paginates up to 100 records per page.
