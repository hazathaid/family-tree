# Phase 3 Database Documentation

Phase 3 adds family member profile storage.

## family_members

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| family_id | BIGINT | Foreign key to families |
| family_branch_id | BIGINT | Nullable foreign key to family_branches |
| full_name | VARCHAR(255) | Required, indexed |
| nickname | VARCHAR(255) | Nullable |
| gender | ENUM(male,female) | Nullable |
| birth_date | DATE | Nullable, indexed |
| birth_place | VARCHAR(255) | Nullable |
| is_alive | BOOLEAN | Defaults to true, indexed |
| death_date | DATE | Nullable, indexed |
| death_place | VARCHAR(255) | Nullable |
| biography | TEXT | Nullable |
| profile_photo | VARCHAR(255) | Public disk path |
| profile_photo_thumbnail | VARCHAR(255) | Public disk thumbnail path |
| created_by | BIGINT | Foreign key to users |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |
| deleted_at | TIMESTAMP | Soft delete |

Relationships remain normalized. Derived family relationship names are not stored in this table.
