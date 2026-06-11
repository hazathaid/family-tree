# Phase 2 Database Documentation

Phase 2 adds family management, family roles, branches, and Spatie RBAC tables.

## Spatie Permission Tables

The following package tables support `spatie/laravel-permission`:

```text
permissions
roles
model_has_permissions
model_has_roles
role_has_permissions
```

Phase 2 creates global role names `owner`, `admin`, and `member` through `FamilyRoleCatalogService` when family role operations run.

## families

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| name | VARCHAR(255) | Required |
| slug | VARCHAR(255) | Unique, generated from name |
| description | TEXT | Nullable |
| origin_city | VARCHAR(255) | Nullable, indexed |
| logo | VARCHAR(255) | Nullable |
| cover_image | VARCHAR(255) | Nullable |
| created_by | BIGINT | Foreign key to users |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |
| deleted_at | TIMESTAMP | Soft delete |

## family_user_roles

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| family_id | BIGINT | Foreign key to families |
| user_id | BIGINT | Foreign key to users |
| role | ENUM(owner,admin,member) | Active family role |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |
| deleted_at | TIMESTAMP | Soft delete |

Active membership uniqueness is enforced in `FamilyRoleService` so removed members can be invited again after soft delete.

## family_branches

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| family_id | BIGINT | Foreign key to families |
| name | VARCHAR(255) | Required |
| description | TEXT | Nullable |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |
| deleted_at | TIMESTAMP | Soft delete |

## Dashboard Source Tables

Phase 2 does not create `family_members`, `articles`, `member_photos`, or `events`. `FamilyDashboardRepository` checks whether those tables exist and counts them when later phases add them.
