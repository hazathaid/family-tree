# API Specification

Project: Family Tree Platform Indonesia

Version: 1.0

Base URL

```text
/api/v1
```

Authentication

```text
Laravel Sanctum
Bearer Token
```

Response Format

Success:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Error:

```json
{
  "success": false,
  "message": "Validation Error",
  "errors": {}
}
```

---

# AUTHENTICATION

## Register

POST /auth/register

Request

```json
{
  "name": "Ahmad",
  "email": "ahmad@mail.com",
  "phone": "08123456789",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Response

```json
{
  "success": true,
  "message": "Registration successful"
}
```

---

## Login

POST /auth/login

Request

```json
{
  "email": "ahmad@mail.com",
  "password": "secret123"
}
```

Response

```json
{
  "token": "xxxx",
  "user": {}
}
```

---

## Logout

POST /auth/logout

---

## Me

GET /auth/me

---

# FAMILY

## Create Family

POST /families

Request

```json
{
  "name": "Keluarga Besar Ahmad",
  "description": "Trah Ahmad",
  "origin_city": "Bandung"
}
```

---

## List Families

GET /families

---

## Family Detail

GET /families/{id}

---

## Update Family

PUT /families/{id}

---

## Delete Family

DELETE /families/{id}

---

# FAMILY MEMBERS

## Create Member

POST /family-members

Request

```json
{
  "family_id": 1,
  "first_name": "Budi",
  "last_name": "Santoso",
  "nickname": "Budi",
  "gender": "male",
  "birth_place": "Bandung",
  "birth_date": "1980-01-01",
  "is_alive": true
}
```

Response

```json
{
  "success": true,
  "data": {
    "id": 1001
  }
}
```

---

## List Members

GET /family-members

Query Parameters

```text
family_id
search
gender
is_alive
page
limit
```

---

## Member Detail

GET /family-members/{id}

---

## Update Member

PUT /family-members/{id}

---

## Delete Member

DELETE /family-members/{id}

---

## Upload Profile Photo

POST /family-members/{id}/photo

multipart/form-data

```text
photo
```

---

# RELATIONSHIPS

## Create Relationship

POST /relationships

Request

```json
{
  "family_uuid": "family-public-uuid",
  "source_member_uuid": "source-member-public-uuid",
  "target_member_uuid": "target-member-public-uuid",
  "relationship_type": "father"
}
```

Allowed `relationship_type` values:

```text
father
mother
child
husband
wife
```

The API also accepts numeric `family_id`, `source_member_id`, and `target_member_id` for compatibility with older clients.

---

## Update Relationship

PUT /relationships/{relationship_uuid}

Request fields match Create Relationship. Updates are rejected when they would create an invalid graph.

---

## Relationship Detail

GET /relationships/{relationship_uuid}

---

## Delete Relationship

DELETE /relationships/{relationship_uuid}

---

## List Relationships

GET /relationships

Parameters

```text
family_uuid
member_uuid
limit
```

Numeric `family_id` and `member_id` filters are also accepted.

Validation rules:

```text
One biological father only.
One biological mother only.
No circular parent relationship.
Husband and wife inverse edges stay consistent.
Relationship updates preserve graph integrity.
```

---

# RELATIONSHIP ENGINE

## Calculate Relationship

Menentukan hubungan otomatis.

GET /relationship-engine

Parameters

```text
source_member_id
target_member_id
```

Response

```json
{
  "relationship": "Sepupu"
}
```

Contoh:

```text
Pakde
Bude
Om
Tante
Sepupu
Keponakan
Menantu
Mertua
```

---

# FAMILY TREE

## Generate Tree

GET /tree/generate

Parameters

```text
member_id
mode
depth
```

mode

```text
ancestor
descendant
full
```

Response

```json
{
  "tree": {}
}
```

---

## Export PNG

GET /tree/export/png

Parameters

```text
member_id
mode
```

Response

```text
binary image
```

---

## Export PDF

GET /tree/export/pdf

Parameters

```text
member_id
mode
```

---

# DASHBOARD

## Dashboard Summary

GET /dashboard

Response

```json
{
  "total_members": 1000,
  "alive_members": 800,
  "deceased_members": 200,
  "total_articles": 150,
  "upcoming_events": 12
}
```

---

# ARTICLES

## Create Article

POST /articles

Request

```json
{
  "family_id": 1,
  "category_id": 1,
  "title": "Sejarah Keluarga",
  "content": "..."
}
```

---

## List Articles

GET /articles

Query

```text
family_id
category_id
search
```

---

## Article Detail

GET /articles/{id}

---

## Update Article

PUT /articles/{id}

---

## Delete Article

DELETE /articles/{id}

---

# ARTICLE COMMENTS

## Add Comment

POST /articles/{id}/comments

Request

```json
{
  "comment": "Artikel yang sangat menarik"
}
```

---

## List Comments

GET /articles/{id}/comments

---

# ARTICLE LIKES

POST /articles/{id}/like

DELETE /articles/{id}/like

---

# PHOTO ARCHIVE

## Upload Photo

POST /photos

multipart/form-data

Fields

```text
family_id
member_id
title
photo
description
```

---

## List Photos

GET /photos

---

## Photo Detail

GET /photos/{id}

---

## Delete Photo

DELETE /photos/{id}

---

# EVENTS

## Create Event

POST /events

Request

```json
{
  "title": "Reuni Keluarga",
  "description": "Reuni tahunan",
  "event_date": "2026-12-10 09:00:00",
  "location": "Bandung"
}
```

---

## List Events

GET /events

---

## Event Detail

GET /events/{id}

---

## Update Event

PUT /events/{id}

---

## Delete Event

DELETE /events/{id}

---

# EVENT RSVP

POST /events/{id}/rsvp

Request

```json
{
  "status": "yes"
}
```

status

```text
yes
no
maybe
```

---

# NOTIFICATIONS

## My Notifications

GET /notifications

---

## Mark Read

POST /notifications/{id}/read

---

## Mark All Read

POST /notifications/read-all

Optional list query parameters:

```text
status=read|unread
limit=1..100
page
```

---

# PUSH DEVICES

## Register Device

POST /push-devices

```json
{
  "platform": "android",
  "token": "provider-device-token"
}
```

Supported platforms:

```text
android
ios
```

## Remove Device

DELETE /push-devices/{device_uuid}

---

# TIMELINE

## Family Timeline

GET /timeline

Query

```text
family_id
page
limit
```

Response

```json
[
  {
    "type": "MEMBER_CREATED",
    "message": "Budi ditambahkan ke keluarga"
  }
]
```

---

# SEARCH

## Global Search

GET /search

Parameters

```text
keyword
family_id
```

Response

```json
{
  "members": [],
  "articles": [],
  "events": []
}
```

---

# REPORTS

## Family Statistics

GET /families/{family_uuid}/reports/family-statistics

Response

```json
{
  "total_generations": 8,
  "total_members": 350,
  "alive_members": 270,
  "deceased_members": 80
}
```

## Activity Reports

GET /families/{family_uuid}/reports/activity

Optional query parameters:

```text
from (date)
to (date, after or equal to from)
```

Returns active users, photo upload totals, and article totals for the selected period. The default period is the last 30 days.

---

# MOBILE SPECIFIC

## Sync Dashboard

GET /mobile/dashboard

## Sync Notifications

GET /mobile/notifications

## Sync Timeline

GET /mobile/timeline

---

# ADMIN

## User Management

GET /admin/users

GET /admin/users/{id}

PUT /admin/users/{id}

DELETE /admin/users/{id}

---

## Family Management

GET /admin/families

DELETE /admin/families/{id}

---

## Audit Logs

GET /admin/audit-logs

---

# HEALTH CHECK

GET /health

Response

```json
{
  "status": "ok"
}
```
