# Consolidated REST API Specification

Status: single mobile contract audited against `routes/api.php`, controllers, Form Requests, Resources and Phase 1–16 API notes on 2026-07-22.

## Protocol

Base path is `/api/v1`. Send `Accept: application/json`; authenticated endpoints require `Authorization: Bearer <Sanctum token>`. JSON requests use UTF-8; files use `multipart/form-data`. Route model bindings shown as `{family}`, `{article}`, etc. resolve public UUIDs through models; clients must always send UUIDs.

Unless stated as binary, successful responses use:

```json
{"success":true,"message":"Success","data":{}}
```

Created resources return 201; deletes commonly return 200 with `data:null`. Validation returns 422:

```json
{"success":false,"message":"Validation Error","errors":{"field":["message"]}}
```

Authentication, authorization, missing resource, conflict/domain validation, throttle and server failures use 401, 403, 404, 409/422, 429 and 5xx respectively with the same safe error intent. Internal exception/class/trace/SQL must never appear. Clients must tolerate a missing `errors` object outside validation.

## Pagination

Laravel paginators serialized through Resource collections place the item list under `data.data` and paginator navigation/count fields alongside it (for example `current_page`, `last_page`, `per_page`, `total`, `links`, `first_page_url`, `last_page_url`, `next_page_url`, `prev_page_url`). Some bounded feeds use `page`/`limit` without a full total. Flutter must parse both documented shapes through a typed adapter and must not infer more pages from item count when explicit metadata exists. Default page size is generally 15; public `limit`/`per_page` is capped at 100 unless stated otherwise.

## Endpoint catalog

Legend: Public means no bearer token; all others require authentication and applicable policy/family membership.

### Health and authentication

| Method/path | Request | Response / authorization |
|---|---|---|
| GET `/health` | none | service/dependency status; Public; 200 or 503 |
| POST `/auth/register` | `name`, `email`, `password`, `password_confirmation`, optional phone | User resource; Public guest |
| POST `/auth/login` | `email`, `password`, optional device name | `{user,token}`; Public guest; login throttle |
| POST `/auth/logout` | none | revokes current token |
| GET `/auth/me` | none | User resource |
| POST `/auth/forgot-password` | `email` | neutral reset dispatch message; Public guest |
| POST `/auth/reset-password` | `token`, `email`, password + confirmation | reset result; Public guest |
| POST `/auth/email/verification-notification` | none | send/already-verified result |
| GET `/auth/email/verify/{id}/{hash}` | signed query | verification result; signed, authenticated |

### Profile

| Method/path | Request | Response |
|---|---|---|
| GET `/profile` | none | User resource |
| PUT `/profile` | name/email/phone fields accepted by `UpdateProfileRequest` | updated User |
| PATCH `/profile/password` | current password, password + confirmation | null |
| POST `/profile/avatar` | multipart `avatar`, image jpg/jpeg/png/webp <=5 MB | updated User |

There is no REST notification-preference or session list/revoke endpoint; see API-G01/FT-API-101.

### Families, roles, branches and dashboard

| Method/path | Request / filters | Response |
|---|---|---|
| GET `/families` | pagination | authorized Family collection |
| POST `/families` | name; optional description/origin/privacy fields per request | Family, creator becomes owner |
| GET/PUT/DELETE `/families/{family}` | update fields on PUT | Family / updated / null; policy guarded |
| GET `/families/{family}/roles` | pagination | memberships/roles |
| POST `/families/{family}/roles/invite` | email, role | membership invitation/result |
| PATCH `/families/{family}/roles/{membership}` | role | updated membership; last-owner protected |
| DELETE `/families/{family}/roles/{membership}` | none | null; last-owner protected |
| GET/POST `/families/{family}/branches` | POST name, optional description | paginated branches / created branch |
| GET/PUT/DELETE `/families/{family}/branches/{branch}` | update name/description | branch / updated / null; nested family invariant |
| GET `/families/{family}/dashboard` | none | totals/generations/living/deceased/birthdays/recent activity summary |

Family logo/cover web multipart behavior is not represented by the current REST update contract; see API-G02.

### Members and relationships

| Method/path | Request / filters | Response |
|---|---|---|
| GET `/family-members` | `family_uuid` plus supported search/filter/page parameters | paginated FamilyMember resources |
| POST `/family-members` | `family_uuid`, `full_name`; optional branch UUID, nickname, gender, birth, `is_alive`, death, biography | created member |
| GET/PUT/DELETE `/family-members/{family_member}` | PUT member fields; death required when not alive | member / updated / soft-deleted null |
| POST `/family-members/{family_member}/photo` | multipart `photo`, image <=10 MB in current request | updated member with photo |
| GET `/relationships` | family/member filters + pagination | base relationship resources |
| POST `/relationships` | family/source/target UUID, type; optional dates/notes | base relationship; type only father/mother/child/husband/wife |
| GET/PUT/DELETE `/relationships/{relationship}` | update base-edge fields | relationship / updated / null |
| GET `/relationship-engine` | `source_member_uuid`, `target_member_uuid` | `{relationship,path}`; same family; derived server-side |

The member REST list does not yet expose all server-side filters/sorts used by the Phase 17 web directory; see API-G03.

### Tree and binary export

| Method/path | Query | Response |
|---|---|---|
| GET `/tree/generate` | root member UUID, mode ancestor/descendant/full, depth 1–20, layout accepted by request | Tree resource: root/mode/depth/layout/nodes/edges/viewport/statistics/cached |
| GET `/tree/export/png` | root/mode/depth/layout/paper_size | `image/png` bytes with attachment disposition; 10/min |
| GET `/tree/export/pdf` | same | `application/pdf` bytes with attachment disposition; 10/min |

Binary endpoints do not use the JSON success envelope on success. Errors remain safe JSON. See API-G04 for layout/lazy expansion alignment.

### Articles

| Method/path | Request / filters | Response |
|---|---|---|
| GET/POST `/article-categories` | GET search/page; POST name, optional description | collection / category (super-admin management) |
| GET/PUT/DELETE `/article-categories/{category}` | update name/description | category / updated / null |
| GET `/articles` | family UUID, category/status/search/limit/page supported by controller | authorized paginated articles |
| POST `/articles` | family/category UUID, title, content; optional excerpt/status | article |
| GET/PUT/DELETE `/articles/{article}` | update article fields | details / updated / null |
| POST `/articles/{article}/publish` | none | published article |
| POST `/articles/{article}/featured-image` | multipart image <=10 MB per request | updated article; 10/min |
| GET/POST `/articles/{article}/comments` | pagination / `comment` | comments / created comment; POST 30/min |
| PUT/DELETE `/articles/{article}/comments/{comment}` | `comment` on PUT | updated / null |
| POST/DELETE `/articles/{article}/like` | none | like summary; 60/min |
| POST/DELETE `/articles/{article}/feature` | none | updated article; authorized admin |
| GET `/families/{family}/articles/featured` | `limit` <=100 | featured article collection |

### Photos, timeline, events and notifications

| Method/path | Request / filters | Response |
|---|---|---|
| GET/POST `/photo-albums` | family UUID/page / family UUID, name, description | albums / created album |
| GET/PUT/DELETE `/photo-albums/{album}` | name/description update | album / updated / null |
| GET `/member-photos` | family/album/member UUID, page/limit | paginated photos |
| POST `/member-photos` | multipart family UUID, optional album UUID, image <=10 MB, caption/captured_at | photo |
| GET/DELETE `/member-photos/{photo}` | none | details / null |
| PUT `/member-photos/{photo}/tags` | `member_uuids[]`, max 100, same family | updated photo |
| GET `/timeline` | optional family UUID, type articles/photos/events/members, limit<=100, page | bounded activity feed |
| GET/POST `/events` | family/search/date filters / family UUID, title, future event_date, description/location | paginated events / event |
| GET/PUT/DELETE `/events/{event}` | update event fields | event / updated / null |
| POST `/events/{event}/rsvp` | status yes/no/maybe | attendee; 30/min |
| GET `/notifications` | page/filters in request | current-user notifications |
| POST `/notifications/read-all` | none | `{updated}` |
| POST `/notifications/{notification}/read` | none | updated notification |
| POST `/push-devices` | platform android/ios, token | device resource; 20/min |
| DELETE `/push-devices/{device}` | none | null, owner only |

### Search, reports and gamification

| Method/path | Request / filters | Response |
|---|---|---|
| GET `/search` | keyword/family UUID/name/city/generation with root UUID/status alive/deceased/limit<=100 | grouped authorized SearchResource results; 60/min |
| GET `/families/{family}/reports/family-statistics` | none | counts and demographic/generation aggregates |
| GET `/families/{family}/reports/activity` | `date_from`, `date_to` | bounded activity report |
| GET `/families/{family}/gamification` | none | current user's points/badges in family |
| GET `/families/{family}/leaderboard` | limit<=100 | user leaderboard |
| GET `/leaderboard/families` | limit<=100 | family leaderboard |

Generation report/search parity remains an explicit contract gap API-G05.

### Super-admin (web-console support, excluded from mobile)

All `/admin/*` endpoints require the platform `administer` ability/super-admin role.

| Method/path | Request | Response |
|---|---|---|
| GET `/admin/users` / GET `/admin/users/{user}` | per_page<=100 | user list/detail |
| PATCH `/admin/users/{user}` | status active/suspended | updated user; self-suspension denied; suspension revokes tokens |
| GET `/admin/families` / GET `/admin/families/{family}` | per_page<=100 | moderated family list/detail/counts |
| DELETE `/admin/families/{family}/content` | content_type article/photo/event, content_uuid | null; ownership checked |
| GET `/admin/audit-logs` | action/type/date range/per_page | paginated audit logs |
| GET `/admin/audit-logs/export` | same filters | CSV attachment, max 10,000 rows |

## Resource field policy

Resources expose UUID and presentation/domain fields only. User resources must omit password, remember token, raw Sanctum tokens, session payload/IP and internal IDs. Family-scoped resources expose related UUIDs, never authorize based on nested client data. Media URLs are generated through configured storage and may be temporary; clients must not persist them as identity. Relationship path and tree edge references use member UUIDs.

## RBAC and family isolation

Authentication is necessary but insufficient: every model action calls its policy or a service family-membership guard. Owner/admin/member capabilities are defined in `business-rules.md`. Super-admin endpoints are not a bypass for mobile. Nested branch/role/report routes verify that the nested object belongs to the route family. Cross-family UUID probing must result in 403/404 without resource data.

## Rate limiting

All v1 endpoints use `throttle:api`. Additional route limits are: login named limiter; tree PNG/PDF and article image 10/min; comments and RSVP 30/min; likes 60/min; push registration 20/min; search 60/min. Clients honor 429 and `Retry-After` and do not spin retries.

## REST gaps against Phase 17 web

| ID | Missing/misaligned REST capability | Owning task |
|---|---|---|
| API-G01 | Notification preferences plus safe session list/revoke | FT-API-101 |
| API-G02 | Family logo, cover, privacy/settings audit against web | FT-API-202 |
| API-G03 | Member directory parity for gender/living/branch/sort server-side filters | Must be closed before/within FT-MOB-301 via approved API work |
| API-G04 | Tree layouts differ (web request omits radial), no lazy expansion or stable relationship-to-root label | FT-API-301 |
| API-G05 | Generation-aware search/report contract needs alignment and bounded output | FT-API-401 |
| API-G06 | Rich mobile dashboard content exceeds current summary (activity/birthdays/events/notification/facts/recent members contract) | FT-API-201 |
| API-G07 | No explicit active-family REST selection is needed for stateless mobile, but onboarding create/select semantics must be defined client-side from memberships | FT-MOB-103; add API only if server state is approved |
| API-G08 | Web article upload route name differs but REST capability exists; no gap. Web-only super-admin console is intentionally excluded. | Documented decision |

Historical phase API documents remain implementation history. This file is the sole Flutter contract; when routes/requests/resources change, update this file and tests in the same task.
