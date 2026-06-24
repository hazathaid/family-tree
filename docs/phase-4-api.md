# Phase 4 API Documentation

Base URL:

```text
/api/v1
```

All endpoints require Sanctum bearer authentication and return the standard project response envelope.

## Relationships

### List Relationships

```http
GET /relationships
```

Filters:

```text
family_uuid
member_uuid
limit
```

Numeric `family_id` and `member_id` filters are also accepted.

### Create Relationship

```http
POST /relationships
```

Body:

```json
{
  "family_uuid": "family-public-uuid",
  "source_member_uuid": "source-member-public-uuid",
  "target_member_uuid": "target-member-public-uuid",
  "relationship_type": "father",
  "start_date": null,
  "end_date": null,
  "notes": "Optional notes"
}
```

Allowed relationship types: `father`, `mother`, `child`, `husband`, `wife`.

### View Relationship

```http
GET /relationships/{relationship_uuid}
```

### Update Relationship

```http
PUT /relationships/{relationship_uuid}
```

Body fields match Create Relationship. Updates are rejected when they break graph integrity.

### Delete Relationship

```http
DELETE /relationships/{relationship_uuid}
```

Deletes the relationship using soft delete. For husband/wife relationships, the inverse spouse edge is soft deleted as well.
