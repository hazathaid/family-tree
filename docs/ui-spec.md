# UI Specification

Status: consolidated from web Phase 17 and current assets on 2026-07-22.

## Design direction and tokens

The product is calm, trustworthy and readable across generations. Mobile uses native composition while preserving meaning and brand.

| Token | Value |
|---|---|
| Primary / dark | `#1E88E5` / `#1565C0` |
| Secondary | `#43A047` |
| Accent | `#FB8C00` |
| Danger | `#E53935` |
| Background / surface | `#F5F7FA` / `#FFFFFF` |
| Text / muted / border | `#243447` / `#64748B` / `#DCE3EA` |
| Radius | 12px default; 8px controls; pill for badges |
| Elevation | subtle shadow equivalent to `0 4px 16px rgba(36,52,71,.08)` |
| Font | Inter when bundled; platform sans-serif fallback |

Spacing uses a 4dp grid: 4, 8, 12, 16, 24, 32 and 40. Body text is 16sp; supporting 14sp; title 20–24sp; display 28–32sp. Do not encode meaning with color alone.

## Responsive behavior

- Small phone: 320–374dp, single column, compact padding 12dp.
- Standard/large phone: 375–599dp, single column, 16dp padding.
- Tablet: 600–1023dp, navigation rail and one/two-column adaptive content.
- Wide tablet: >=1024dp, rail/sidebar and bounded content width.
- Text scaling must remain usable through 200%; tables become cards or horizontally scroll with an explicit affordance.

Dark mode decision: out of initial parity scope. Follow system brightness only after dark tokens and contrast/golden tests are delivered; until then use the audited light theme rather than an incomplete automatic dark theme.

## Mobile navigation

Unauthenticated stack: splash/session bootstrap, login, register, forgot/reset password and verification. Authenticated root uses destinations Dashboard, Family/Tree, Activity, and More; a navigation rail replaces bottom navigation on tablet. More contains members, articles, photos, events, search, reports, gamification, family settings and account. Owner/admin actions are conditionally discoverable but server authorization remains mandatory. Family selector is available from the app bar/profile area.

## Reusable components

App shell, page header, family selector, search field with debounce, filter sheet, primary/secondary/destructive buttons, text/select/date/file fields, cards, list tiles, avatar/memorial avatar, role/status badges, statistic card, paginated list/footer, confirmation dialog, snackbar, progress indicator, skeleton, empty state, error/retry state, stale/offline banner, upload progress, tree controls/canvas/node and semantic icon button.

Controls have >=48x48dp target, visible focus, enabled/disabled/loading states and plain-language validation. Destructive confirmation names the affected item. Skeletons approximate final layout and stop under reduced-motion settings.

## Standard async states

- Initial loading: skeleton or centered progress, semantic announcement once.
- Empty: meaningful illustration/icon, explanation and permitted primary action.
- Validation: inline field error plus summary/focus to first error.
- Authorization: explain lack of access without revealing hidden data.
- Offline/stale: persistent banner, last-updated time and explicit retry.
- Server error: generic message and retry; never render exception/debug text.
- Pagination: preserve existing content on next-page failure and offer retry.
- Mutation: disable duplicate submit, show progress, success feedback and refreshed authoritative data.

## Screen inventory and web mapping

| Web page/flow | Flutter destination |
|---|---|
| Landing | No parity screen; store listing/deep link entry |
| Login/register/forgot/reset/verify | Auth flow screens |
| Onboarding/family activate | Family setup and selector flow |
| Dashboard | Adaptive dashboard with drill-down cards |
| Profile/preferences/password | Account, preferences and security screens |
| Family settings/branches/memberships | Family settings subsections, role-gated |
| Member list/create/edit/detail | Directory, member form and detail |
| Tree viewer | Full-screen interactive tree with controls |
| Articles index/form/show | Article list/editor/detail |
| Photos/albums | Gallery, album, upload and detail/tag flows |
| Events index/form/show | Event list/form/detail/RSVP |
| Timeline/notifications | Activity feed and notification inbox |
| Search | Global search with grouped results |
| Reports | Family statistics and activity reports |
| Admin console | Web-only; intentionally no Flutter screen |

## Accessibility

Every image has contextual semantics or is excluded if decorative. Icon-only controls have labels/tooltips. Traversal order follows visual order. Dynamic status changes use polite announcements. Contrast targets WCAG AA (4.5:1 normal, 3:1 large/UI). Tree nodes expose name, relationship to root, living/deceased status and expand action independent of canvas gestures. Pinch/drag has button alternatives for zoom/reset and keyboard/focus alternatives on supported devices.
