# Tej Printbrands — Performance & SEO Optimization Report

Scope was determined by auditing the actual codebase against a broader 6-phase brief; several
assumed problems turned out to already be solved (see "Already done" below). This report covers
only the work actually performed, with real measurements — no estimates.

## Already done before this pass (no work needed)

| Item | Evidence |
|---|---|
| Admin/public JS bundle separation | `vite.config.js` builds `resources/js/app.ts` (5.3KB) and `resources/js/admin/main.ts` (933KB) as separate entries; each layout loads only its own bundle. |
| Vue booking form lazy-load | N/A — the booking page is a plain Blade form, no Vue involved. |
| Tailwind purge/config | Already a clean, modern Tailwind v4 CSS-native config. |
| WebP conversion pipeline + GIF rejection | `app/Services/ImagePipeline.php` + `config/images.php` already generate thumb/card/hero WebP variants on upload and reject GIF uploads. |

## What was fixed

### 1. SEO metadata (previously ~0% coverage)

Before: only `<title>` was dynamic per page. No meta description, keywords, Open Graph, Twitter
Card, canonical link, or structured data existed anywhere in the codebase.

After:
- `resources/views/layouts/site.blade.php`: meta description/keywords, full OG + Twitter Card tags,
  canonical link, sitewide `LocalBusiness` JSON-LD — all sourced live from the existing
  `SiteSetting` DB records (`company`/`contact`/`socials`), the same data already used on the
  contact page. No invented business facts.
- All 10 public pages now have page-specific meta description, keywords, canonical override, and
  `BreadcrumbList` schema via a new `partials/breadcrumb-schema.blade.php` partial.
- Additional structured data: `Organization` (home), `Service` (service-detail), `Product`/`ItemList`
  (products), `Article` (blog-detail), `ImageObject` (gallery).
- Verified: every public route renders 200 with valid, parseable JSON-LD (checked via
  `json_decode()` on each emitted `<script type="application/ld+json">` block).

Bug caught and fixed during implementation: a literal `@context` key written directly inside a
Blade `{!! !!}` echo (outside an `@php` block) gets misparsed as Laravel's `@context` Blade
directive, corrupting the JSON output. Fixed by building every schema array inside `@php ... @endphp`
first, then echoing the pre-built variable.

### 2. Images

Initial concern was "6-8MB unoptimized images." Investigation found:
- Total `storage/app/public/uploads`: **7.7MB**, not 6-8MB — and of that, only **8 images** are
  actually referenced by any live database record (the rest are hotlinked Unsplash/Clearbit URLs,
  handled by the existing component, or orphaned uploads from replaced/deleted content that no page
  ever requests).
- All 8 live-referenced images already had `-thumb`/`-card`/`-hero` WebP conversions generated —
  running `php artisan images:backfill-conversions` confirmed 0 new conversions needed (8
  already-converted, 38 external/hotlinked, 0 generated).
- The real gap: `service-detail`, `products`, `blog-detail`, `services`, `work`, and `booking` page
  templates were serving raw `<img>` tags pointed at the **full-size originals** — bypassing
  conversions that already existed on disk. Two concrete examples:
  - `FFRrUTuRpTzwSIlUZ4iLoK0HNOFuOFA8RXY7dKEL.webp`: 1.4MB original, 248KB `-hero` conversion sat unused.
  - `mtNKY7GXxcFfhDHCJedSsxtWhM6AaDZ7SwkYfQqE.webp`: 1.0MB original, 107KB `-hero` conversion sat unused.
- Fixed: all 15 raw `<img>` tags across those 6 templates now use the existing
  `<x-responsive-image>` component (lazy-loading, `decoding="async"`, correct WebP variant + srcset),
  matching the pattern already used in `sections/hero.blade.php`, `sections/portfolio.blade.php`, etc.
- `public/assets/images/printing.jpg` (a static asset outside the DB pipeline, used as a hero/fallback
  background): resized 3000x2001 → 1920x1281 and recompressed to quality 80 using the same
  Intervention Image/GD stack the upload pipeline already uses.
  **343,516 bytes → 201,172 bytes (41% smaller).**

### 3. Fonts

Before: `display=swap` was already present, but delivered via a render-blocking CSS `@import` in
`resources/css/app.css`, which meant the browser had to download and parse the whole CSS bundle
before it even discovered the font stylesheet — wasting the `<link rel="preconnect">` hints already
present in the layout.

After: font stylesheet now loads via a `<link rel="stylesheet">` tag directly in `<head>`, right
after the preconnects, so the connection setup actually pays off. `app.css` build output shrank from
137,548 bytes to 124,656 bytes as a side effect.

### 4. HTTP caching headers

Before: `public/.htaccess` was the stock Laravel default — zero cache directives, so every asset was
refetched on every visit.

After: added `mod_expires`/`mod_headers` (1-year immutable `Cache-Control` for JS/CSS/images/fonts,
no-cache for HTML), `mod_deflate` gzip compression, and safe security headers
(`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`). Skipped CSP — the site has inline
`<script>` blocks (e.g. `booking.blade.php`'s service-selector logic) that a strict policy would
need a dedicated audit to allow for safely. Config syntax verified with a standalone
`apache2ctl -t` test against a minimal vhost loading the same modules.

## Verified build output (measured, not estimated)

| Asset | Size | Notes |
|---|---|---|
| `app-*.js` (public site) | 5.29 KB | vanilla TS + axios only, no Vue/Pinia |
| `index-*.js` (shared axios chunk) | 41.99 KB | |
| `app-*.css` (public site) | 124.66 KB (was 137.55 KB) | Tailwind v4, font `@import` removed |
| `main-*.js` (admin only) | 933.68 KB | Vue/Pinia/Router/TipTap — never sent to public visitors |
| `main-*.css` (admin only) | 4.12 KB | |
| `printing.jpg` | 201.17 KB (was 343.52 KB) | -41% |

`npm run build` completes cleanly with no new warnings beyond the pre-existing admin chunk-size
notice (expected — that bundle is never served to public visitors).

## Explicitly out of scope

- Admin bundle internal code-splitting (route-level lazy loading within the 933KB admin SPA) — not
  requested, and the admin bundle is already isolated from the public site.
- CSP header — deferred pending a dedicated inline-script audit.
- The ~3 orphaned original images sitting in `storage/app/public/uploads` with no live model
  reference — left untouched since deleting storage files wasn't in scope and they cost nothing
  (never served to a visitor).
