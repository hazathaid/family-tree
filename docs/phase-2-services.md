# Phase 2 Service Documentation

Phase 2 follows the existing Repository Pattern, Service Layer Pattern, DTO Pattern, and API Resource pattern.

## DTOs

### `FamilyData`

Carries create/update family input:

```text
name
description
originCity
logo
coverImage
```

### `FamilyRoleData`

Carries family invite input:

```text
email
role
```

### `FamilyBranchData`

Carries branch create/update input:

```text
name
description
```

### `FamilyDashboardData`

Carries dashboard aggregate output:

```text
totalMembers
livingMembers
deceasedMembers
totalArticles
totalPhotos
totalEvents
```

## Repositories

### `FamilyRepositoryInterface`

Handles family persistence, lookup by UUID, user-scoped pagination, slug uniqueness checks, update, and delete.

### `FamilyUserRoleRepositoryInterface`

Handles family membership persistence, active membership lookup, Owner counting, restore-or-create invitation, update, and delete.

### `FamilyBranchRepositoryInterface`

Handles branch persistence, lookup by UUID, family-scoped pagination, update, and delete.

### `FamilyDashboardRepositoryInterface`

Handles aggregate counts for dashboard widgets. It uses schema checks because source tables for members, articles, photos, and events are implemented in later phases.

## Services

### `FamilyService`

Responsibilities:

* Create families.
* Generate unique slugs.
* Assign the creator as Owner.
* Update family details.
* Soft delete families.
* Provide dashboard cache keys.

### `FamilyRoleService`

Responsibilities:

* Invite existing users into a family.
* Assign family roles.
* Remove family members.
* Enforce that each family always has at least one Owner.
* Prevent removing or demoting the last Owner.
* Sync global Spatie roles from active family memberships.

### `FamilyBranchService`

Responsibilities:

* Create family branches.
* Update family branches.
* Delete family branches.
* Reject operations on branches from another family.

### `FamilyDashboardService`

Responsibilities:

* Build family dashboard summary.
* Cache dashboard data for five minutes through the configured Laravel cache store.

## Policies

### `FamilyPolicy`

Rules:

* Any active user may create a family.
* Active family members may view the family and dashboard.
* Owners and admins may update family data and manage branches.
* Only Owners may delete family data and manage roles.

### `FamilyBranchPolicy`

Rules:

* Active family members may view branches.
* Owners and admins may update or delete branches.
