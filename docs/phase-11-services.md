# Phase 11 Services - Search

`SearchService` coordinates global and advanced search. `SearchRepositoryInterface` scopes all SQL queries to families accessible by the authenticated user and performs text/status filters.

When generation is requested, `SearchService` reuses `TreeGraphBuilderService` and performs iterative BFS. The root is generation `0`, parents use negative levels, children positive levels, and spouses remain at the same level.
