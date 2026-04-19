# Verification log — laravel/livewire-starter-kit

Docker-verified observation of upstream `laravel/livewire-starter-kit`, and
the production-hardening gaps that motivate commits on this fork's
`improvements` branch.

The Livewire variant shares the same Laravel backend shape as the React and
Vue starter kits, **but the findings are not identical** — the frontend
architecture (Livewire + Blade, no Inertia, no Wayfinder) changes which
symptoms exist and how fixes are applied. This log records each finding
fresh, with notes on where the Livewire shape differs.

## Environment

All commands run inside `codelift/Dockerfile` (php:8.5-cli-bookworm + Node 22).
No language runtime is installed on the host (CodeLift spec §5-6).

| Item | Value |
|---|---|
| Verification date | 2026-04-19 |
| Upstream commit | `laravel/livewire-starter-kit@62c60c8` on `main` |
| Base image | `php:8.5-cli-bookworm` |
| PHP | 8.5.5 |
| Laravel Framework | 13.x |
| Composer | 2.9.7 |
| Node / npm | 22.22.2 / 10.9.7 |
| Frontend | Livewire v4 + Flux + Alpine |
| DB for tests | SQLite |

## Baseline

Upstream `main`, no modifications: `docker compose run --rm app` returns
**33 passed (77 assertions), 52s**. Fewer tests than the React/Vue variants
(40/136) because Livewire-based settings pages are tested through a Livewire
component harness rather than the Inertia HTTP harness used by the others.

## Issue catalogue

### A. Setup order (no Wayfinder — gotcha does not apply, but README lacks a Setup section)

**STATUS: partially valid.** Unlike the React/Vue variants, Livewire's
`vite.config.js` does **not** include `@laravel/vite-plugin-wayfinder`, so
`npm run build` before `composer install` does not fail with the opaque
`require(vendor/autoload.php)` error the other variants produce. That
particular gotcha is Livewire-safe.

However, the upstream README still has no "Setup" section; a new contributor
has to infer the right sequence from context.

**Fix**: add a README Setup section showing the clean sequence (composer →
.env → key:generate → sqlite → migrate → npm → build → test). No runtime
implication, just contributor-friendliness.

### B. `.env.example` ships development defaults with no production hints

**STATUS: valid.** Identical to React/Vue.

**Fix**: inline production-override comments.

### C. `config/app.php` hardcodes the timezone

**STATUS: valid.** `config/app.php:68` is `'timezone' => 'UTC'`.

**Fix**: `env('APP_TIMEZONE', 'UTC')`.

### D. No security response headers in the middleware stack

**STATUS: valid — and stricter than React/Vue's state.** The Livewire
`bootstrap/app.php` calls `withMiddleware(function (Middleware $middleware) {
//
})` with no appended middleware at all (React/Vue appended three
Inertia-related ones). Every response lacks CSP / HSTS / X-Frame-Options /
X-Content-Type-Options / Referrer-Policy / Permissions-Policy.

**Fix**: same `SetSecurityHeaders` middleware, appended to the web group via
`$middleware->web(append: [SetSecurityHeaders::class])`.

**Livewire-specific note on CSP**: the React/Vue CSP policy kept
`'unsafe-inline'` in `script-src` because Inertia serializes initial props
into an inline `<script>`. Livewire does **not** do that — it uses
`wire:*` attributes on HTML elements to carry state and loads its client via
external script URLs. A strict CSP without `'unsafe-inline'` is theoretically
achievable, **but** Flux (shadcn-like component library shipped with this
starter) and Alpine-based UI often inject inline event handlers or styles
that would need nonce support. For this pass we keep the same permissive
CSP as React/Vue (production only, `'unsafe-inline'` retained); tightening
it to nonce-based is a follow-up.

### E. No HTTPS scheme enforcement helper

**STATUS: valid.** No `forceScheme`, `forceHttps`, `TrustProxies` in `app/`
or `config/`.

**Fix**: `URL::forceScheme('https')` in `AppServiceProvider::configureDefaults`
guarded by `app()->isProduction()`.

### F. ~~Password rules~~

**STATUS: RESOLVED UPSTREAM.** Same as React/Vue: `AppServiceProvider` already
installs strong `Password::defaults` in production (min 12, mixed case,
numbers, symbols, `uncompromised()`).

### G. Login throttle is per email+IP, no account lockout

**STATUS: valid.** `FortifyServiceProvider::configureRateLimiting` applies a
single `Limit::perMinute(5)->by($email.'|'.$ip)`. Identical symptom to
React/Vue.

**Fix**: layered `RateLimiter::for('login', ...)` returning an array.

### H. 2FA available but not policy-enforced

**STATUS: design gap, not a defect.** Same as other variants.

### I. No separated logging channel for auth events

**STATUS: valid.** `config/logging.php` ships the default stack → single
layout.

**Fix**: `auth` daily channel + `AuthActivitySubscriber` subscribing to
`Illuminate\Auth\Events\*` plus Fortify 2FA enable/disable.

### J. ~~Settings endpoints: only password update is throttled~~

**STATUS: not applicable in the React/Vue shape.** In Livewire starter,
`routes/settings.php` uses `Route::livewire(...)` to render component pages,
and mutations (profile update, password change, 2FA enable) happen through
Livewire's internal AJAX endpoint (`/livewire/update`) not through per-field
HTTP verbs. Throttling at the route level only covers initial page loads,
not component actions; per-component rate limits belong inside the component
classes via `RateLimiter::tooManyAttempts` in the action methods.

This is a real production concern but the fix shape is substantively
different from React/Vue. Deferred to a separate Livewire-specific article
rather than bolted onto this pass.

## Upgrade plan

Applied in this order, each commit standalone, each keeps `docker compose
run --rm app` green.

1. **A** — README Setup section (contributor friendliness; no runtime impact)
2. **B** — `.env.example` production-override comments
3. **C** — `config/app.php` env-driven timezone
4. **E** — `AppServiceProvider`: `URL::forceScheme('https')` in production
5. **D** — `SetSecurityHeaders` middleware + web-group registration + test
6. **G** — layered login rate limiter
7. **I** — `auth` log channel + `AuthActivitySubscriber` + test

Total: 7 commits (React/Vue had 8). The omitted finding (J) requires a
different approach for Livewire and is deferred, not ignored.
