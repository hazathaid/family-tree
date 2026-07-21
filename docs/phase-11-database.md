# Phase 11 Database - Search

Phase 11 introduces no new table. Search reads the existing indexed `family_members`, `articles`, `events`, and `member_relationships` tables.

Generation values are computed dynamically with BFS from the requested root member. They are not persisted, preserving the relationship graph as the source of truth.
