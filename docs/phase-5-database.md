# Phase 5 Database Documentation

Phase 5 adds only the FT-508 cache table. Family tree source data remains `family_members` and `member_relationships`.

## `member_tree_cache`

| Field | Type | Notes |
| --- | --- | --- |
| `id` | BIGINT | Primary key |
| `uuid` | UUID | Unique public identifier |
| `family_id` | BIGINT | Foreign key to `families`, cascade delete |
| `member_id` | BIGINT | Root member FK to `family_members`, cascade delete |
| `mode` | VARCHAR(20) | `ancestor`, `descendant`, or `full` |
| `depth` | SMALLINT UNSIGNED | Requested traversal depth |
| `tree_json` | LONGTEXT/JSON | Generated graph and statistics |
| `generated_at` | TIMESTAMP | Generation time |
| `expires_at` | TIMESTAMP | 24-hour TTL boundary |
| `created_at` | TIMESTAMP | Audit field |
| `updated_at` | TIMESTAMP | Audit field |

Required indexes:

```text
uuid UNIQUE
member_id, mode, depth UNIQUE
family_id, expires_at
```

The initial cache stores layout-independent tree data. Layout coordinates are calculated for each request so one cached graph can serve vertical, horizontal, and radial rendering.

## Cache Lifecycle

* Cache entries expire 24 hours after generation.
* Creating, updating, restoring, or deleting a family member invalidates that family's tree cache.
* Creating, updating, restoring, or deleting a base relationship invalidates that family's tree cache.
* Expired rows are ignored and removed by a scheduled cleanup command.
* Cache invalidation occurs after a successful database transaction.

## Data Integrity Rules

* Every cached root member must belong to `family_id`.
* `mode` accepts only `ancestor`, `descendant`, and `full`.
* `depth` is limited to 1-20 by request validation and service validation.
* Cache data must never be treated as the graph source of truth.
* Derived relationships such as Pakde, Bude, Sepupu, and Menantu are never stored as graph edges.

## Capacity Considerations

Graph reads must be indexed by `family_id` and streamed or processed in chunks. The generator uses numeric member IDs internally for memory-efficient lookup while exposing only UUIDs through the API. Full-tree traversal uses an iterative queue and visited set, never recursion.
