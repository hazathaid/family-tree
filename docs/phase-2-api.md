# Phase 2 API Documentation

Base URL:

```text
/api/v1
```

All endpoints require Sanctum bearer authentication.

## Families

### List Families

```http
GET /families
```

Returns families where the authenticated user has an active `family_user_roles` membership.

### Create Family

```http
POST /families
```

Body:

```json
{
  "name": "Keluarga Besar Ahmad",
  "description": "Trah Ahmad",
  "origin_city": "Bandung"
}
```

Creates a family and assigns the creator as `owner`.

### View Family

```http
GET /families/{family_uuid}
```

Requires active family membership.

### Update Family

```http
PUT /families/{family_uuid}
```

Requires `owner` or `admin`.

### Delete Family

```http
DELETE /families/{family_uuid}
```

Requires `owner`.

## Family Roles

### List Family Roles

```http
GET /families/{family_uuid}/roles
```

Requires `owner`.

### Invite Member

```http
POST /families/{family_uuid}/roles/invite
```

Body:

```json
{
  "email": "member@example.com",
  "role": "member"
}
```

`role` must be one of `owner`, `admin`, or `member`.

### Assign Role

```http
PATCH /families/{family_uuid}/roles/{membership_uuid}
```

Body:

```json
{
  "role": "admin"
}
```

The last active `owner` cannot be demoted.

### Remove Member

```http
DELETE /families/{family_uuid}/roles/{membership_uuid}
```

The last active `owner` cannot be removed.

## Family Branches

### List Branches

```http
GET /families/{family_uuid}/branches
```

Requires active family membership.

### Create Branch

```http
POST /families/{family_uuid}/branches
```

Body:

```json
{
  "name": "Cabang Jakarta",
  "description": "Keluarga wilayah Jakarta"
}
```

Requires `owner` or `admin`.

### View Branch

```http
GET /families/{family_uuid}/branches/{branch_uuid}
```

Requires active family membership.

### Update Branch

```http
PUT /families/{family_uuid}/branches/{branch_uuid}
```

Requires `owner` or `admin`.

### Delete Branch

```http
DELETE /families/{family_uuid}/branches/{branch_uuid}
```

Requires `owner` or `admin`.

## Family Dashboard

### Dashboard Summary

```http
GET /families/{family_uuid}/dashboard
```

Response data:

```json
{
  "total_members": 2,
  "living_members": 1,
  "deceased_members": 1,
  "total_articles": 2,
  "total_photos": 1,
  "total_events": 1
}
```

Dashboard data is cached for five minutes. Future-phase tables that do not exist yet are counted as `0` without creating those modules in Phase 2.
