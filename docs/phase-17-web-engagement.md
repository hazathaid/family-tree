# Phase 17 Step 6 — Family Content and Engagement

Step 6 adds the authenticated Blade interfaces for articles, family photos, events, the family timeline, and user notifications. All pages use the active family stored in the session and reuse the existing repositories, policies, validation requests, and domain services.

## Web routes

- `/articles`: filtered article cards, detail, editor, publish, comments, likes, and cover upload.
- `/photos` and `/albums/{album}`: paginated gallery, upload, album detail, photo detail, and member tagging.
- `/events`: upcoming/all event lists, event management, attendee list, and `yes`, `no`, or `maybe` RSVP.
- `/timeline`: paginated active-family activity with member, photo, article, and event filters.
- `/notifications`: user-scoped notification list and mark-read actions.

Article HTML is sanitized by `RichTextSanitizer` before persistence. Draft visibility and every mutation are controlled by the existing policies. Family photo uploads accept JPG, JPEG, PNG, or WebP up to 10 MB and generate thumbnails. Dates are rendered using `config('app.timezone')`.

No database schema or REST API behavior changed in this step.
