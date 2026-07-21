# Tree Generation Engine Specification

Status: aligned with current Laravel tree services, requests and web viewer on 2026-07-22.

## Authoritative contract

The server receives an authorized root member UUID, mode and depth; graph traversal and statistics remain in Laravel. Modes are `ancestor`, `descendant`, and `full`. Depth is an integer 1–20. The response contains root UUID, mode, depth, nodes, edges, statistics and cache indicator. Layout enrichment adds layout, positioned nodes and viewport. Public references in nodes/edges are UUIDs.

## Graph build and traversal

`TreeGraphBuilderService` builds a family-scoped adjacency list from non-deleted members and the five base relationship types. Parent/child edges carry generation delta; spouse edges carry zero delta. `FamilyTreeService` performs iterative BFS from root with a queue and visited set:

- ancestor accepts only edges toward earlier generations;
- descendant accepts only edges toward later generations;
- full accepts both directions and spouse edges;
- traversal stops expanding a node at requested depth;
- each member appears once, preventing cycles and runaway traversal.

No recursive traversal or derived relationship rows are used.

## Nodes, edges and labels

Nodes include public identity/display fields needed by the tree, generation/distance, living state and photo where available. Deceased nodes remain present and UI adds a memorial marker. Edges include source UUID, target UUID and stored/normalized relationship. A relationship-to-root label for every node is not currently guaranteed by the generate response; FT-API-301 must define it before mobile relies on it.

## Layouts

Current `TreeLayoutService` supports `vertical`, `horizontal`, `radial`, and `compact` positioning. Vertical is the baseline generation-axis layout; horizontal swaps axes; compact reduces spacing; radial places generations around root. Layout is deterministic presentation metadata over the same graph and does not alter kinship.

Web controls currently expose the supported layouts through the tree viewer. API request validation and documentation must agree on exact accepted values; FT-API-301 owns any change.

## Lazy expansion

Current endpoint generates a bounded tree by depth in one request; there is no explicit slice cursor/expand contract. Mobile must not simulate expansion by downloading the whole family. FT-API-301 must specify either:

1. repeat generation with the same root and larger bounded depth, or
2. a new node expansion endpoint returning a deduplicatable slice (`nodes`, `edges`, boundary/has_more).

Until then, mobile uses only documented bounded generation and replaces the graph on parameter changes.

## Cache and invalidation

`member_tree_cache` key is member+mode+depth, with family, serialized JSON, generated/expiry timestamps. TTL is 24 hours in repository behavior. Cache hit returns `cached: true`. Member or relationship mutation invalidates relevant family tree cache; relationship mutation also invalidates relationship cache. Layout may be applied after graph retrieval and must not corrupt cached canonical graph.

## Export

Authenticated rate-limited endpoints provide PNG and PDF binary downloads. Inputs use the same root/mode/depth plus layout/paper-size options validated by `ExportTreeRequest`. Supported print sizes are A4, A3 and A2 where request/service validation allows. Response must set safe content type/disposition and return bytes, not the JSON envelope. Current PNG/PDF services render synchronously and are relatively simple; historical queued/headless-render recommendations are not the actual contract and require a future task.

## Performance and safety boundaries

- Generation target: <5 seconds for supported bounded requests in a 100,000-member family.
- Depth maximum 20 protects pathological expansion but does not guarantee a small result in a broad graph; response-size/load testing is required.
- BFS is O(V+E) for the included family graph and O(V) memory; repositories must use family and endpoint indexes.
- Visited sets handle cycles defensively. Write-time parent cycle prevention remains mandatory.
- Authorization and family isolation precede cache lookup to prevent cache-based disclosure.

## Mobile rendering requirements

Pan/zoom must be incremental and smooth, with reset/fit controls and accessible node navigation. Do not render all 100,000 nodes. Preserve selected root/mode/depth/layout across transient rebuilds, cancel stale requests, and announce loading/errors. Node tap opens an authorized member detail. Export shows progress, supports cancellation/share, and treats binary failures safely.

## Verification

Tests cover each mode, depth boundaries, root isolation, parent/child/spouse graph normalization, cycle-safe traversal, unique nodes, UUID edges, deceased marker data, cache hit and invalidation, all accepted layouts, invalid parameters, PNG/PDF MIME/content/disposition, paper sizes, rate limit and performance fixtures. FT-API-301 must add contract tests for lazy expansion and relationship-to-root if introduced.

## Known gaps

- No explicit lazy slice/expand protocol.
- Relationship-to-root label is not a stable per-node contract.
- Layout validation may differ between generate/export/web paths and must be reconciled by FT-API-301.
- Export is synchronous and not production-grade high-resolution headless rendering.
