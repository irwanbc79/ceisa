# PRD · CEISA H2H · M2B Customs (ceisa.m2b.co.id)

## Original Problem Statement
> coba UI nya beautifikasi dan improve lebih baik lagi untuk ceisa.m2b.co.id yang ada di github/ceisa saya, berikan ide, kreasi dan nilai seni mu yang hebat dan brilian...

**Stack**: Laravel 11 (Blade) + Tailwind v3 + Alpine.js + Vite. Repo: github.com/irwanbc79/ceisa.
**Brand context**: Pilot project untuk morabangun.com — portfolio piece pertama untuk jualan jasa H2H Bea Cukai.

## Design Direction (locked)
- **Theme name**: *Maritime Indigo*
- **Palette**: deep navy `--ink #0B1437` · warm cream `--cream #FBF8F2` · antique gold `--gold #C9A55C` · sea teal `--sea #0E867E` · crimson `--crimson #B73239`
- **Typography**: Fraunces (display serif, opsz variable) + Plus Jakarta Sans (Indonesian-designed body) + JetBrains Mono
- **Motifs**: gold underline-on-hover, editorial italic emphasis, eyebrow labels with dash, donut ring stats, compass watermark, navy code blocks, ticker bands

## What's Implemented (Jan 2026)
- ✅ Foundation: `tailwind.config.js` (full brand tokens), `resources/css/app.css` (design system components: `.btn`, `.card`, `.pill`, `.nav-side`, `.stat`, `.ink-hero`, `.topo-bg`, shimmer, ticker, staggered entrance)
- ✅ Layouts: sidebar app shell (`layouts/app.blade.php` + `navigation.blade.php`), split-screen guest (`layouts/guest.blade.php`)
- ✅ Marketing landing (`welcome.blade.php`): full hero + ticket card mock + module grid + 4-step H2H flow + dark code sample + benefits + KPI strip + CTA + footer. Root route updated to show this for guests.
- ✅ Auth: login, register, forgot/reset/confirm password, verify-email — all redesigned editorial
- ✅ Dashboard (`dashboard.blade.php`): editorial KPI hero with SVG donut ring chart, quick actions, recent docs table
- ✅ Documents: `index.blade.php` (KPI rekap + filter bar + table), `lookup.blade.php` (real-time query UI), `arsip.blade.php` (manual import form), `create.blade.php` & `show.blade.php` (full wizard polish to ink/gold/sea/cream tokens)
- ✅ Settings: `settings/ceisa.blade.php` (credential form refined)
- ✅ Profile: `profile/edit.blade.php` + 3 partials (info, password, delete)
- ✅ Components: primary/secondary/danger buttons, text-input, label, input-error, nav-link, responsive-nav-link, dropdown, dropdown-link, status-badge, jalur-badge, flash, auth-session-status
- ✅ Soft-launch metrics (Jan 2026): all marketing claims replaced with qualitative labels (Pilot Phase · Q1 2026 · Sandbox-tested · TLS 1.3 · AES-256 · 24/7). No fabricated KPI numbers.
- ✅ Build verified: `yarn build` produces `public/build/assets/app-*.css` (~88 kB)
- ✅ Smoke-tested end-to-end with PHP local server + Playwright screenshots

## Backlog · P1
- Empty-state illustrations for documents index (custom SVG instead of generic icon)
- Mobile (sm) refinement for sidebar transition timings
- Dark mode toggle (currently light only)

## Backlog · P2
- Marketing page micro-interactions: cursor-follow gold spotlight on hero, magnetic CTA
- Animated SVG counters on landing KPI strip
- Per-doc-type colored gradient strip on dashboard rows
- API docs page (developer-focused), public route
- Add a "Customers" / case-study slot on landing for social proof

## Next Action Items
1. User pulls the changes, runs `yarn install && yarn build && php artisan view:clear`
2. Optional: tweak `welcome.blade.php` copy or KPI numbers to match real M2B metrics
3. Optional: polish wizard internals (P1) in subsequent iteration
