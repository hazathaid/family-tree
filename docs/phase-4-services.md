# Phase 4 Service Documentation

Phase 4 follows Repository Pattern, Service Layer Pattern, Form Requests, and API Resources.

## Repositories

### `RelationshipRepositoryInterface`

Handles relationship persistence, UUID lookup, user-scoped pagination, duplicate edge checks, biological parent lookup, and parent-edge retrieval for graph validation.

## Services

### `RelationshipService`

Responsibilities:

* Create, update, and delete stored base relationship edges.
* Reject unsupported relationship types.
* Ensure source and target members belong to the selected family.
* Enforce one biological father per child.
* Enforce one biological mother per child.
* Prevent circular parent relationships using BFS over normalized parent edges.
* Keep husband and wife inverse edges consistent on create, update, and delete.
* Preserve graph integrity during updates by validating the proposed graph before persistence.

The service stores only `father`, `mother`, `child`, `husband`, and `wife`. Derived relationship labels are reserved for later relationship resolver tasks.
