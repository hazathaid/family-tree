# Phase 3 Service Documentation

Phase 3 follows Repository Pattern, Service Layer Pattern, Form Requests, and API Resources.

## Repositories

### `FamilyMemberRepositoryInterface`

Handles member persistence, UUID lookup, user-scoped pagination, update, and soft delete.

## Services

### `FamilyMemberService`

Responsibilities:

* Create family members.
* Update member profile fields.
* Ensure selected branches belong to the member family.
* Soft delete members through the repository.
* Store member profile photos on the public disk.
* Generate member photo thumbnails automatically.

## Policies

### `FamilyMemberPolicy`

Rules:

* Active family members may list and view profiles in their families.
* Owners and admins may create, update, delete, and upload member photos.
