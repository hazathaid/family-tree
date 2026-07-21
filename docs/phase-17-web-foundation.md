# Phase 17 Step 1 — Web Foundation

FT-1701 establishes the shared Blade presentation layer without changing backend data or API behavior.

## Entry points

- Vite compiles `resources/css/app.css` and `resources/js/app.js`.
- `/` is a named direct view route (`home`) and demonstrates the public layout.
- Bootstrap 5 provides responsive navigation, offcanvas, modal, alert, and form behavior. No additional JavaScript framework is used.

## Design system

CSS custom properties define the UI specification palette, typography, surface, border, radius, and shadow values. Inter is the preferred font, followed by Arial and the generic sans-serif fallback. Layout behavior targets mobile below 768 px, tablet from 768–1024 px, and desktop above 1024 px.

## Shared layouts and components

Layouts are available as `layouts.base`, `layouts.public`, `layouts.guest`, `layouts.app`, and `layouts.error`. Navigation is split into top, desktop sidebar, mobile offcanvas, and footer components.

Reusable components include `button`, `form.input`, `form.select`, `card`, `badge`, `alert`, `pagination`, `modal`, `empty-state`, and `loading-state`. Form components expose server validation errors with visible invalid styling and accessible descriptions.

All primary navigation supports keyboard focus, a skip link is provided, active links use `aria-current`, mobile navigation manages focus, and reduced-motion preferences are respected.
