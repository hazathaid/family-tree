# Phase 4 Database Documentation

Phase 4 introduces stored relationship graph edges for FT-401 and FT-402.

## member_relationships

| Field | Type | Notes |
| --- | --- | --- |
| id | BIGINT | Primary key |
| uuid | UUID | Unique public identifier |
| family_id | BIGINT | Foreign key to families |
| source_member_id | BIGINT | Foreign key to family_members |
| target_member_id | BIGINT | Foreign key to family_members |
| relationship_type | ENUM | father, mother, child, husband, wife |
| start_date | DATE | Nullable |
| end_date | DATE | Nullable |
| notes | TEXT | Nullable |
| created_at | TIMESTAMP | Audit field |
| updated_at | TIMESTAMP | Audit field |
| deleted_at | TIMESTAMP | Soft delete |

Indexes:

```text
uuid
family_id
source_member_id
target_member_id
relationship_type
family_id, source_member_id, target_member_id, relationship_type
```

Only base graph relationships are stored. Derived kinship names such as pakde, bude, om, tante, sepupu, keponakan, menantu, mertua, buyut, and cicit are not persisted.
