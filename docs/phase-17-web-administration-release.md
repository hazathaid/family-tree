# Phase 17 Step 8 — Administration and Release Quality

## Administration console

The Blade administration console is available under `/admin` and reuses the Phase 15 administration and audit services. Every administration route requires the `administer` gate, which is limited to users with the `super-admin` role.

The console provides a dashboard, paginated user management, family moderation, and filterable CSV audit-log export. Content moderation requires explicit confirmation and records the actor, target, request IP address, and user agent in `audit_logs`. Internal exception details are not rendered.

## Release hardening

The web application includes keyboard focus indicators, a skip link, semantic table captions, responsive typography, reduced-motion support, and mobile navigation focus management. Versioned Vite assets receive immutable one-year cache headers from Nginx, with an application fallback. Custom Indonesian error pages are provided for HTTP 403, 404, 419, 422, 429, and 500.

Web smoke tests cover public entry points, security headers, error rendering, super-admin route authorization, moderation confirmation, audit creation, filtering, and CSV export.
