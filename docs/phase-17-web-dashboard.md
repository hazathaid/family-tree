# Phase 17 Step 3 — Web Dashboard

FT-1705 provides a cache-aware, responsive application home for the active family. It does not change the database schema or REST API.

## Route and authorization

`GET /dashboard` uses the authenticated, verified, and active-family middleware chain. `DashboardController` resolves the active family from the session and enforces `FamilyPolicy::viewDashboard` before requesting presentation data.

## Presentation service

`WebDashboardService` combines the existing `FamilyDashboardService` statistics with bounded repository queries for recent activity, birthdays in the next seven days, upcoming events, recently added members, notifications, and family facts. Family-wide widgets are cached for five minutes using `web-dashboard:family:{id}`. User notifications remain outside the shared family cache and are restricted to the authenticated user and active family.

## User interface

The Blade dashboard follows `ui-spec.md`: welcome banner, six statistic cards, social-style activity feed, upcoming birthdays and events, notification summary, family facts, and recently added members. Every widget provides an explicit empty state and the layout collapses cleanly for mobile and tablet widths.

## Verification

Feature tests cover active-family scoping, authorization, populated and empty widget states, and a two-second response target with 1,000 family members. Unit coverage verifies presentation facts and family-widget caching.
