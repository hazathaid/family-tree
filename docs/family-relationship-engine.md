# Family Relationship Engine Specification

Status: aligned with current services/tests on 2026-07-22.

## Contract and invariants

Input uses source and target member UUIDs in the same authorized family. Output is a localized relationship label or `null`, an ordered path, and connectivity represented by the response/cache contract. `source == target` returns `Saya` with an empty path.

Only five base edges may be persisted: `father`, `mother`, `child`, `husband`, `wife`. Sibling, grandparent, Pakde/Bude/Om/Tante, cousin, nephew/niece, parent/child-in-law, Buyut and Cicit are derived. Optional adoptive/guardian/step relationships from historical notes are not supported by the migration and must not be accepted.

## Edge normalization

Stored edges are normalized for traversal:

- `source --father/mother--> target`: from source to target is parent; reverse is child.
- `source --child--> target`: from source to target is child; reverse is parent inferred from source gender where available.
- `husband`/`wife`: traversed both ways as spouse; write service maintains inverse stored spouse edge.
- Soft-deleted members/relationships and cross-family edges are excluded by repository queries.

## BFS

`RelationshipTraversalService::shortestPath` uses an iterative queue, a visited set keyed by member ID, and predecessor edges. It loads neighbors through the family-scoped repository, stops when target is found, reconstructs the ordered shortest path, and enriches each hop with member UUID/name and base-edge metadata. An exhausted queue returns an empty disconnected path. The visited set makes malformed cyclic data traversal-safe.

Shortest path is deterministic only to the extent repository neighbor ordering is deterministic. If multiple equally short kinship paths exist, callers must not assume a particular alternative path unless ordering is added to the contract.

## Derived relationship matrix

| Normalized path from source | Target qualifier | Label |
|---|---|---|
| empty and same member | — | Saya |
| father / mother / parent | target gender/path | Ayah / Ibu / Orang Tua |
| child | male/female/other | Anak Laki-Laki / Anak Perempuan / Anak |
| spouse | target gender | Suami / Istri / Pasangan |
| parent,parent | target gender | Kakek / Nenek / Kakek/Nenek |
| child,child | target gender | Cucu Laki-Laki / Cucu Perempuan / Cucu |
| 3+ all parent | — | Buyut (extended ancestor label per resolver) |
| 3+ all child | — | Cicit (extended descendant label per resolver) |
| parent,child (not self) | target gender | Saudara Laki-Laki / Saudara Perempuan / Saudara |
| parent,parent,child | gender + relative birth age when available | Pakde/Bude or Om/Tante |
| parent,parent,child,spouse | spouse gender/context | Bude/Tante or Om/Pakde-equivalent per resolver |
| parent,parent,child,child | — | Sepupu |
| parent,child,child | target gender | Keponakan Laki-Laki / Perempuan / Keponakan |
| spouse,parent | target gender | Mertua Laki-Laki / Perempuan / Mertua |
| child,spouse | target gender | Menantu Laki-Laki / Perempuan / Menantu |

The PHP resolver is the executable naming authority. Mobile displays its label/path verbatim and does not recreate this matrix in Dart. Unknown patterns return a safe generic/null result rather than inventing kinship.

Pakde/Bude versus Om/Tante uses available birth dates to compare the parent's sibling with the parent. Missing/equal dates fall back to the resolver's generic/younger naming behavior; clients must not guess age order.

## Integrity and cycle handling

Before write, `RelationshipService` rejects self-reference, duplicate base edge, second biological father/mother and a parent edge that would create a cycle. Parent-cycle detection is iterative over existing parent edges. Spouse inverses are created/updated/deleted in the same database transaction. Runtime BFS remains cycle-safe even if legacy malformed data exists.

## Cache and invalidation

`member_relationship_cache` is keyed by family/source/target, contains label, JSON path, connectivity and `expires_at`, with 24-hour TTL. Connected and disconnected outcomes are cached. Relationship create/update/delete invalidates the entire family's relationship and tree cache. Member mutations invoke member/family invalidation through member services. Expired rows are ignored and can be purged.

## Performance boundary

Target relationship resolution is <500 ms for a family of up to 100,000 members. BFS memory is O(V) and examined edges O(V+E). Repository neighbor lookups rely on family/source/target indexes. Performance tests must cover near, distant, disconnected and cyclic-corruption fixtures; production monitoring records duration without names/path PII.

## Required verification matrix

Unit/feature coverage must include Ayah, Ibu, Kakek, Nenek, Pakde, Bude, Om, Tante, Sepupu, Keponakan, Menantu, Mertua, Buyut and Cicit; self, spouse, sibling, disconnected, cross-family denial, duplicate parent, self-edge, cycle rejection, inverse spouse, cache hit/expiry/invalidation are also required. Relationship engine coverage target is >=95%.

## Known alignment issues

- Current API route is GET `/api/v1/relationship-engine`; query UUID naming must remain consistent with its Form Request and is finalized for mobile by FT-MOB-305/FT-API-301 as applicable.
- Exact labels for very deep/ambiguous and equal-length paths are implementation-defined today; add contract tests before localization expansion.
- Historical specifications included unsupported adoptive/guardian/step edges; these are explicitly out of current scope.
