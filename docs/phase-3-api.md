# Phase 3 API Documentation

Base URL:

```text
/api/v1
```

All endpoints require Sanctum bearer authentication and return the standard project response envelope.

## Family Members

### List Members

```http
GET /family-members
```

Returns members from families where the authenticated user has an active family role.

### Create Member

```http
POST /family-members
```

Body:

```json
{
  "family_uuid": "family-public-uuid",
  "family_branch_uuid": "optional-branch-public-uuid",
  "full_name": "Siti Aminah",
  "nickname": "Siti",
  "gender": "female",
  "birth_date": "1980-01-10",
  "birth_place": "Bandung",
  "is_alive": true,
  "death_date": null,
  "death_place": null,
  "biography": "Riwayat singkat anggota keluarga."
}
```

Owners and admins may create members. `death_date` is required when `is_alive` is `false`, and cannot be earlier than `birth_date`.

### View Member

```http
GET /family-members/{member_uuid}
```

Active family members may view member profiles.

### Update Member

```http
PUT /family-members/{member_uuid}
```

Owners and admins may update member profiles.

### Delete Member

```http
DELETE /family-members/{member_uuid}
```

Owners and admins may soft delete members.

### Upload Profile Photo

```http
POST /family-members/{member_uuid}/photo
Content-Type: multipart/form-data
```

Fields:

```text
photo: jpg, jpeg, png, or webp image; max 10 MB
```

Stores the original image on the public disk and generates a thumbnail automatically.
