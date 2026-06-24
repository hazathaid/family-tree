# Phase 4 Service Documentation

Phase 4 follows Repository Pattern, Service Layer Pattern, Form Requests, and API Resources.

## Repositories

### `RelationshipRepositoryInterface`

Handles relationship persistence, UUID lookup, user-scoped pagination, duplicate edge checks, biological parent lookup, parent-edge retrieval for graph validation, and family graph edge retrieval for traversal.

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

The service stores only `father`, `mother`, `child`, `husband`, and `wife`.

### `RelationshipTraversalService`

Responsibilities:

* Build an in-memory family graph from `family_members` and `member_relationships`.
* Normalize stored base edges into traversal moves: parent, child, and spouse.
* Use Breadth First Search to find the shortest path from source member to target member.
* Track visited nodes during BFS so spouse and parent-child cycles do not create infinite traversal.
* Return a path of member-to-member steps without storing derived relationships.

### `RelationshipResolverService`

Responsibilities:

* Resolve source and target members from the traversal path.
* Classify supported Indonesian relationship names from the shortest path.
* Support: Saya, Ayah, Ibu, Kakek, Nenek, Saudara Laki-Laki, Saudara Perempuan, Pakde, Bude, Om, Tante, Sepupu, Keponakan, Menantu, Mertua, Buyut, and Cicit.
* Use birth dates when available to distinguish Pakde/Bude from Om/Tante.
* Return `null` when members are in the same family but no graph path connects them.

Derived relationship labels are calculated dynamically and are never stored in `member_relationships`.
