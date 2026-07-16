# Phase 5 Service Documentation

Phase 5 covers FT-501 through FT-508 and follows Repository Pattern, Service Layer Pattern, Form Requests, API Resources, and thin controllers. This document defines the intended implementation order and responsibilities.

## FT-501 Graph Builder

### `TreeRepositoryInterface`

Responsibilities:

* Stream active family members required for graph nodes.
* Stream non-deleted base relationships for one family.
* Read and persist `member_tree_cache` entries.
* Avoid loading Eloquent relations per node.

### `TreeGraphBuilderService`

Responsibilities:

* Receive family members and relationships through the repository layer.
* Build adjacency lists keyed by numeric member ID.
* Normalize stored father, mother, child, husband, and wife edges into traversable directions.
* Skip dangling or cross-family edges defensively.
* Never query the database directly and never create derived relationship edges.

## FT-502 Ancestor Tree

### `AncestorTreeService`

Uses iterative BFS from the selected root. Only parent moves are accepted. The root has generation `0`, parents `-1`, grandparents `-2`, and traversal stops at the requested depth.

## FT-503 Descendant Tree

### `DescendantTreeService`

Uses iterative BFS from the selected root. Only child moves are accepted. The root has generation `0`, children `+1`, grandchildren `+2`, and traversal stops at the requested depth.

## FT-504 Full Tree

### `FullTreeService`

Uses multi-direction BFS over parent, child, and spouse moves. A visited set prevents cycles and duplicate nodes. The service must:

* Keep the selected member as root.
* Support disconnected family graphs by returning only the connected component.
* Process up to 100,000 connected members without recursion.
* Return stable node and edge ordering for deterministic caching and exports.

### `FamilyTreeService`

Orchestrates cache lookup, graph construction, mode-specific traversal, statistics, and cache persistence. Unsupported modes and depths are rejected even when called outside HTTP.

## FT-505 Tree Renderer

### `TreeLayoutService`

Transforms layout-independent tree data into responsive coordinates:

* `vertical`: generation on the Y axis.
* `horizontal`: generation on the X axis.
* `radial`: root at the center and generations on expanding rings.

Coordinates are deterministic. Node spacing and viewport size grow from the node count, with minimum dimensions suitable for mobile. Layout calculation does not change graph relationships.

### `TreeResource`

Returns node display data, base edges, positions, viewport, root marker, memorial state, and statistics. Profile paths are converted to public URLs by the resource layer.

## FT-506 PNG Export

### `TreePngExportService`

Renders the same positioned tree used by the API into a 300-DPI PNG for A4, A3, or A2. It adds a title, generation date, relationship lines, member cards, statistics, and footer. Implementation must reuse an available project or built-in image API; adding a dependency requires separate approval.

Large exports must enforce pixel and memory limits and return a safe application error when the requested output exceeds configured capacity.

## FT-507 PDF Export

### `TreePdfExportService`

Creates a printable PDF using the same positioned tree and selected paper size. It includes the required header, tree, statistics, and footer. A PDF library may only be introduced with explicit dependency approval; until then, implementation should use an existing project capability or a small built-in-compatible writer.

## FT-508 Tree Cache

### `TreeCacheService`

Responsibilities:

* Read valid entries by root member, mode, and depth.
* Persist layout-independent tree JSON for 24 hours.
* Invalidate every cached tree for a family when member or base relationship data changes.
* Remove expired cache rows through scheduled cleanup.
* Never use cached data after its TTL or as the graph source of truth.

## HTTP Layer

### `GenerateTreeRequest`

Validates member UUID, mode, depth, and layout.

### `ExportTreeRequest`

Extends tree validation with `paper_size` and export-specific limits.

### `FamilyTreeController`

Authorizes access, delegates generation/rendering to services, and returns `TreeResource`. It contains no traversal or layout logic.

### `TreeExportController`

Authorizes access, delegates binary creation, and returns download responses with correct content types. It does not construct graphs or draw nodes directly.

## Required Tests

Unit tests:

* Graph normalization for every stored relationship type.
* Configurable ancestor and descendant depth.
* Full-tree cycles, spouses, duplicate edges, and disconnected components.
* Vertical, horizontal, and radial coordinate generation.
* Cache hit, miss, expiry, and family invalidation.
* Valid PNG signature and dimensions.
* Valid PDF signature and required document text.

Feature tests:

* Authenticated generation for all three modes and layouts.
* Authorization across families.
* Validation errors for invalid mode, depth, layout, and paper size.
* PNG and PDF content type and attachment headers.
* Cache reuse and invalidation after member or relationship changes.

Performance tests must cover representative 1,000-, 10,000-, and 100,000-node graphs against the targets in `tree-generation-engine.md`.
