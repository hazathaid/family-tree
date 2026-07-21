# Phase 7 Service Documentation

`PhotoAlbumService` resolves family UUIDs and handles album create, update, and soft deletion through `PhotoAlbumRepositoryInterface`.

`MemberPhotoService` validates family and album ownership, resizes and compresses uploads using PHP image APIs, creates thumbnails, removes stored files on deletion, and synchronizes member tags only when all members belong to the photo family. `MemberPhotoRepositoryInterface` supplies user-scoped pagination and eager-loaded details.

Controllers remain thin, Form Requests validate all payloads, API Resources hide internal IDs and storage paths, and policies isolate family data. Feature tests cover album creation, upload, storage, tagging, filtering, validation, and outsider access. Unit tests cover tag synchronization and cross-family rejection.
