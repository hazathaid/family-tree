# Phase 17 Step 4 - Family and Member Management

## Scope

FT-1706 through FT-1708 provide the authenticated Blade interface for the active family. All pages require verified authentication and the `active.family` middleware.

## Family settings

`GET /settings` contains profile, branch, access, privacy, and notification tabs. Owner/Admin may update family identity and manage branches; only Owner may invite users or change/remove family roles. Logo uploads accept JPG, PNG, and WebP up to 5 MB; cover images up to 10 MB. Destructive forms require an explicit confirmation value and browser confirmation.

Privacy is membership-scoped through `FamilyPolicy`. Notification text documents the current account-based notification behavior; no unsupported preference data is persisted.

## Member directory

`GET /members` is scoped to the active family and paginated at repository level. It supports combinable URL filters: `search`, `gender`, `is_alive`, `branch`, and `sort` (`newest`, `oldest`, or `name`). Desktop uses a table and mobile uses member cards.

## Member pages

Owner/Admin may create, update, upload/replace a profile photo, or soft-delete a member. All users in the active family may view member details. Forms expose the documented Basic Info, Family Info, Biography, Photos, and Documents tabs. Documents remain an explicit empty state because the current backend does not yet contain a member-document entity.

Profile uploads accept JPG, PNG, and WebP up to 5 MB and reuse `FamilyMemberService` thumbnail creation. Deceased members consistently include the `†` marker. Detail data includes profile, base relationships, and empty states for related photos, articles, and timeline content.

## Architecture and tests

Controllers only resolve active-family context, authorize policies, and call services. Filtered database access is implemented by repository contracts. `WebFamilyManagementService` assembles presentation data. Feature tests cover RBAC, active-family isolation, combined filters, uploads, member CRUD, and memorial presentation; a unit test covers presentation-service delegation.
