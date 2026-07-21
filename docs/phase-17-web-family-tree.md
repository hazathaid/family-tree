# Phase 17 Step 5 - Interactive Family Tree Viewer

## Scope

FT-1710 adds the authenticated Blade family-tree viewer at `GET /tree`. The route requires verified authentication, an active family, and family membership authorization. This step does not implement PNG/PDF export, which belongs to FT-1711.

## Viewer behavior

The viewer supports ancestor, descendant, and full modes; root selection; depth 1–20; and vertical, horizontal, and compact layouts. Mobile user agents default to compact layout with depth three. The toolbar provides pan, zoom, center, expand, collapse, search/focus, and keyboard controls. Nodes expose a detail drawer and filters for living status, photos, nicknames, and relationship labels.

Tree nodes and edges come from `FamilyTreeService` BFS output and `TreeLayoutService`. Relationship-to-root labels are resolved on the server through `RelationshipResolverService`; JavaScript only renders the supplied graph. Increasing depth with “Muat generasi berikutnya” lazily requests the next BFS slice instead of loading an unbounded tree into the browser.

## Security and performance

Root members are checked against the active family. Member selectors are paginated to 50 records and tree generation reuses the existing 24-hour tree and relationship caches. No derived relationship is persisted as source data.

Feature tests cover active-family rendering, controls, server-side labels, input validation, foreign-family isolation, and the mobile default. A unit test covers the presentation service output. The performance fixture renders a 101-member tree and enforces the five-second viewer target.
