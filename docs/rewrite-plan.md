# Modernization Plan: xbtit-3.x → Laravel 11

## Context

xbtit-3.x is a legacy PHP 5.x-era BitTorrent tracker with critical security vulnerabilities and deep technical debt. The goal is a full rewrite using Laravel 11 + Blade templates + Bootstrap 5, executed in phases so the app remains functional throughout. Security is the first priority.

**Critical findings driving this rewrite:**
- Passwords hashed with MD5/SHA1 (six variants; type 1 has no salt)
- `unserialize($_COOKIE[...])` on user-controlled input — unauthenticated RCE risk (`include/functions.php:551,558`)
- No CSRF protection anywhere
- No prepared statements — raw `mysqli_real_escape_string()` concatenation
- No session hardening (no httponly/secure/samesite flags)
- Admin auth token exposed in URL query string (`?user=X&code=Y`) — ends up in access logs and browser history
- No rate limiting or account lockout on login
- Globals everywhere (`$GLOBALS['conn']`, `$CURUSER`, `$btit_settings`)
- Bootstrap 3.3.7 (EOL 2019), jQuery 1.12.4 (EOL 2016), XHTML 1.0 Transitional
- bTemplate engine from 2002 — no compilation, no caching, no inheritance

---

## Phase 0 — Patch at Phase 1 Start

`unserialize()` appears in **four locations** — all must be eliminated before any code goes live:

| Location | Risk |
|---|---|
| `include/functions.php:551` — `unserialize($_COOKIE[$user_cookie_name])` | Unauthenticated RCE — attacker-controlled cookie |
| `include/functions.php:558` — `unserialize($_SESSION['login_cookie'])` | Session-stored unserialize; lower risk but still unsafe |
| `include/config.php:48` — `unserialize(file_get_contents($cache_file))` | Filesystem cache; not user-writable, but eliminates the pattern entirely |
| `include/config.php:288` — `unserialize(base64_decode($btit_settings['announce']))` | DB-sourced announce URL list; migrate to JSON in the `Setting` model |

The app is dev/staging only, so no emergency standalone patch — but these four are the first thing addressed when Phase 1 begins.

---

## Phase 1 — Laravel Foundation + Security

**Goal:** Running Laravel app with complete, hardened auth and security layer in place before any feature porting begins.

### 1.1 Project Bootstrap
- `composer create-project laravel/laravel xbtit-new` alongside existing app
- Configure `.env` pointing to the existing MySQL database
- Enable `strict_mode` in `config/database.php`

### 1.2 Database Migration
- Write Eloquent migrations from `sql/database.sql`
- Convert all tables from **MyISAM → InnoDB** (ACID compliance, foreign keys). For large tables (`peers`, `snatches`), use batched `ALTER TABLE` inside migrations to avoid lock contention.
- Extend `users.password` from `VARCHAR(40)` → `VARCHAR(255)` (bcrypt needs 60, argon2id needs more)
- Replace `users.random` INT (weak, dual-purpose auth/CSRF token, exposed in URLs) with:
  - `remember_token` (Laravel default, session-based)
  - Separate CSRF tokens via Laravel's built-in middleware
  - `users.passkey VARCHAR(64) NOT NULL UNIQUE` — new strong PID (see 1.10)
  - `users.legacy_passkey VARCHAR(40) NULL UNIQUE` — holds old `random` value on future import
  - `users.legacy_passkey_expires_at TIMESTAMP NULL` — when old passkey stops working
- Change `peers.peer_ip` from `VARCHAR(15)` → `VARCHAR(45)` — holds both IPv4 and full-form IPv6; IPv6 client support is deferred but schema must not be reworked on a live peers table later
- Add `audit_logs` table: `user_id`, `action`, `ip`, `created_at` — log login success/fail, password change, admin actions

### 1.3 Authentication & Password Migration
- Use **Laravel Breeze** for auth scaffolding
- All new passwords: `Hash::make()` → Argon2id (Laravel 11 default)
- **Password rehash on login** — the legacy system has six hash types (`pass_type` enum 1–6):
  - Type 1: `md5($password)` — no salt
  - Type 2: `md5(md5($salt).md5($password))`
  - Type 3: `md5($salt.$pwd.$salt)`
  - Type 4: `sha1(md5($salt).$pwd.sha1($salt).$sitesecret)`
  - Type 5: SMF — `sha1(strtolower($user).$pwd)`
  - Type 6: custom hybrid SHA1/MD5
  - Write a `LegacyPasswordVerifier` service with a method per type; on successful legacy verification, re-store with `Hash::make()` and set `pass_type = null`
- **Dormant accounts** (users who never log in keep MD5 forever): after 6 months post-launch, trigger a password-reset email campaign for accounts still on legacy hashes; after 12 months, lock them
- **Email verification**: implement `MustVerifyEmail` with signed URLs (replaces `temp_email` field with no token validation)
- **Password reset**: use Laravel's built-in `password.reset` routes (replaces weak legacy token system)

### 1.4 Session Hardening
- `config/session.php`: `secure = true`, `http_only = true`, `same_site = 'strict'`
- Enable `use_strict_mode` in PHP session config to prevent session fixation
- **Remove `session_id('xbtit')`** (`functions.php:496`) — legacy code sets a fixed literal session ID inside `userlogin()`. If called before `session_start()` this makes all users share a single session (catastrophic). Laravel generates cryptographically random session IDs automatically; never set a fixed session ID.
- **Delete the entire `secsui_cookie_type` system** (`functions.php:546–658`): three modes (uid+md5 cookie, serialized cookie, serialized session value) all replaced by Laravel's session + `remember_token`. The associated settings (`secsui_cookie_type`, `secsui_cookie_name`, `secsui_cookie_items`) are not ported.
- Admin authentication: **session + role middleware only** — never URL query string tokens (`?user=X&code=Y` pattern is eliminated entirely)

### 1.5 CSRF
- Laravel's `VerifyCsrfToken` middleware is global by default
- Exclude only `/announce` route (BitTorrent clients are stateless and cannot send CSRF tokens)

### 1.6 Security Headers Middleware
Create `App\Http\Middleware\SecurityHeaders` and add to the global stack:
```
Content-Security-Policy: default-src 'self'; script-src 'self'; ...
Strict-Transport-Security: max-age=31536000; includeSubDomains
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
```
Force HTTPS: `URL::forceScheme('https')` in `AppServiceProvider` when `APP_ENV=production`.

### 1.7 Rate Limiting & Account Lockout
- `RateLimiter::for('login', ...)` — 5 attempts per minute per IP + username
- `RateLimiter::for('register', ...)` — 3 per hour per IP
- `RateLimiter::for('password-reset', ...)` — 3 per hour per email
- After 10 failed logins: lock account, notify user by email, require manual unlock or password reset

### 1.8 Code-Level Security Rules (enforce from day one)
- **No `unserialize()` anywhere** — not just user-controlled data. Replace all four legacy uses (see Phase 0). Laravel's encrypted cookies and `Cache::` handle serialization safely internally; application code never calls `unserialize()` directly.
- No `@` error suppression — `try/catch` + `Log::error()` instead
- No raw SQL with string interpolation — Eloquent default; any `DB::raw()` use must be flagged in review
- IP ban check: port `bannedip` table lookup to a middleware that runs before all routes

### 1.9 Input & Query Safety

### 1.10 PID/Passkey System
- **Storage**: plain `VARCHAR(64)`, indexed — never hashed. Announce is the hottest path; a single B-tree lookup on a hashed value requires either a rainbow scan or HMAC tokens. Plain + unique index is the right trade-off.
- **Generation**: `bin2hex(random_bytes(16))` → 32 hex chars, 128-bit entropy. Replaces the legacy `INT random` (32-bit, ~4 billion values — brute-forceable).
- **Dual-column lookup during migration overlap**: `User::where('passkey', $pid)->orWhere('legacy_passkey', $pid)->first()` — one query, both columns indexed. On a `legacy_passkey` hit: log it and email the user "your old passkey expires on {date}; re-download your torrents."
- **Rotation**: auto-rotate `passkey` on password change; provide a manual "Regenerate Passkey" button on the account page with a clear warning that existing `.torrent` files will stop announcing immediately.
- **Logging hygiene**: never write raw passkeys to `audit_logs`. Store `substr($passkey, 0, 6) . '…'` for diagnostics.
- **Validation regex**: `/^[A-Za-z0-9]{1,64}$/` during the overlap window (accepts both old INT-derived and new hex values). Tighten to `/^[a-f0-9]{32}$/` once `legacy_passkey` is dropped.
- **Announce URL**: keep `/announce.php` as the route path (clients have it baked into `.torrent` files). Add `/announce` as an alias. Apply the same rules to `/scrape.php` + `/scrape`.
- All queries via Eloquent or `DB::` query builder (prepared statements by default)
- When raw SQL is unavoidable: `DB::select('... WHERE id = ?', [$id])` only
- XSS: Blade's `{{ $var }}` auto-escapes; `{!! !!}` use must be explicitly justified

---

## Phase 2 — Core Feature Ports

Port features one controller at a time.

### 2.1 Routing Layer
- Replace flat-file endpoints (`index.php`, `upload.php`, `details.php`, etc.) with routes in `routes/web.php`
- Protected routes: `Route::middleware('auth')->group(...)`
- Admin routes: `Route::middleware(['auth', 'admin'])->prefix('admin')->group(...)`

### 2.2 Eloquent Models
Start with: `User`, `Torrent`, `TorrentCategory`, `Peer`, `Snatch`, `News`, `Comment`, `Setting`, `BannedIp`

### 2.3 Feature Controllers (in order)
1. **Torrent browsing** — `TorrentController@index`, `@show`
2. **Torrent upload** — `TorrentController@store`
   - Port `include/BEncode.php` + `BDecode.php` → `App\Services\BEncodeService`
   - Validate `.torrent` structure before storing
   - Enforce max file size in PHP (not just `post_max_size`)
   - Allowlist announce URLs via `$TRACKER_ANNOUNCEURLS` equivalent in config
   - Rate limit uploads per user
   - Store files via `Storage::disk('torrents')` — never web-accessible directly
3. **Download** — `TorrentController@download`
4. **User profile/account** — `UserController`
5. **News** — `NewsController`
6. **Admin panel** — `Admin\ConfigController`, `Admin\UserController`, etc.

### 2.4 BitTorrent Announce Endpoint

`announce.php` is **805 lines**. Having read the full file, these are the exact issues and constraints to address when porting:

**Must-preserve behaviours:**
- `ignore_user_abort(true)` (line 34) — the announce MUST complete even if the client disconnects. Add this to `AnnounceController@handle` before anything else.
- `summaryAdd()` batching (lines 92–103, flushed at lines 793–800) — all seeds/leechers/bytes updates are batched into a single `UPDATE` to reduce lock contention under load. Preserve this in `AnnounceService`.
- `sendRandomPeers()` echoes bencoded output directly — refactor to return a string; the controller returns a `Response::make($body, 200, ['Content-Type' => 'text/plain'])`.
- xbtt redirect path (lines 50–84) — thin `header()` redirect to xbtt URL. Port as `return redirect($xbttUrl)` in the controller; this path bypasses all PHP tracker logic.
- **Per-PID concurrency limits** (`config.php:111–115`): `maxpid_seeds` (default 3) and `maxpid_leech` (default 2) — caps on simultaneous seeders/leechers per passkey, an anti-account-sharing measure. Port as configurable `Setting` values; enforce in `AnnounceService` during `start()`.
- **`dynamic_torrents` flag** (`config.php:249`): if enabled, tracker accepts any info_hash without prior registration. `AnnounceService` must check this flag before the torrent-authorization query — if true, skip the authorization check.
- **`peercaching` flag** (`config.php:274`): a runtime peer-table cache. Port as a `Cache::remember()` wrapper in `AnnounceService`; no schema-level cache table needed — Laravel Cache (Redis or file) handles this.

**Concrete security bugs to fix in the port:**
- **`$pid` format validation — do this first, at entry**: validate `$pid` against `/^[A-Za-z0-9]{1,64}$/` immediately on receipt, before it touches any `header()`, SQL, or string. This kills the SQLi at lines 200/210/605/630/645/651/671/694/699/734 (all use `addslashes()`'d `$pid`), the header injection in the xbtt redirect (line 78), and any future use in one rule.
- **IP spoofing — two separate vectors**:
  - `getip()` in `include/common.php:369` trusts `HTTP_CLIENT_IP` and `HTTP_X_FORWARDED_FOR` unconditionally — any client can forge their IP, bypassing bans and per-IP peer limits.
  - `start()` at lines 378–388 also accepts `$_GET['ip']` and uses it as the peer IP if present — gated by `$btit_settings['allow_override_ip']` (default false, `config.php:103`), but the flag itself is the risk.
  - **In the port**: drop the `allow_override_ip` flag entirely — do not port it. Legitimate NAT cases are handled by `TrustProxies`, not by an announce URL parameter. Use `$request->ip()` which reads `REMOTE_ADDR` by default. Configure `App\Http\Middleware\TrustProxies` with the **explicit list of your known reverse-proxy IPs only** — do NOT use the default `'*'` wildcard (that would re-enable the spoofing). With the proxies correctly listed, `$request->ip()` correctly peels one trusted forwarded header; with no proxies listed, it reads REMOTE_ADDR directly.
- **`get_magic_quotes_gpc()` call** (line 133): removed in PHP 8.0 — fatal error. The legacy `stripslashes()` branch is dead code on PHP 8; remove entirely in the port.
- **`@` suppression on queries** (lines 426, 447, 490, 786, 789): masks real errors. Replace with try/catch + `Log::error()`.

**Performance risks to be aware of:**
- `gethostbyaddr($ip)` inside `start()` (line 391) — blocking DNS reverse lookup on every new peer connection. In the port, make this optional and configurable (default off).
- `isFireWalled()` (lines 318–337) — blocking `fsockopen()` to the peer's IP:port with a 10-second timeout. Already guarded by `$GLOBALS['NAT']` flag but that flag defaults to on. Default to off in new config; only enable if explicitly set.
- `ob_start('ob_gzhandler')` (lines 144–156) — do not replicate. Let the web server handle gzip at the transport level.

**Middleware exclusions — this route must skip ALL of:**
- `web` middleware group (session, cookies, CSRF, encryption)
- Authentication middleware
- Rate limiting (BitTorrent clients announce every few minutes from many IPs)
- The `CheckIpBan` middleware (ban check is done inside AnnounceService directly)

**State machine — the four events to port:**
- `started` → `start()` + `sendRandomPeers()` + history log
- `stopped` → `killPeer()` + `sendRandomPeers()` + stats update
- `completed` → peer status seeder + `sendRandomPeers()` + history log
- `` (empty, regular announce) → `collectBytes()` + `sendRandomPeers()`

**BitTorrent protocol compliance (port correctly, don't guess):**
- **Compact peer format (BEP 23)**: clients send `compact=1` to request 6-byte binary peers instead of dict format. `sendRandomPeers()` must support both. Check which the legacy code produces and ensure the port handles both branches.
- **Atomic peer upsert**: use `INSERT ... ON DUPLICATE KEY UPDATE` (Eloquent `updateOrCreate` with unique constraint on `(torrent_id, peer_id)`) — the legacy delete-then-insert races concurrent announces from the same peer.
- **Bencoded error on auth failure**: always return HTTP 200 + `d14:failure reason{n}:{msg}e`. Some clients break on HTTP 4xx. The legacy `show_error()` already does this — preserve the pattern.
- **`numwant` clamp**: cap at 50 (or configurable max) to prevent a client from requesting thousands of peers. The legacy code may not clamp.
- **Return `min interval`** in announce response — prevents aggressive clients from re-announcing faster than the tracker wants.
- **`event=paused` (BEP 21)**: modern clients send this; treat as a regular announce (don't error).
- **IPv6 peers (deferred)**: schema is ready (`peer_ip VARCHAR(45)`); actual IPv6 peer handling ships in a later phase.

**If xbtt C++ backend is used in production:** this controller becomes a 10-line redirect proxy (lines 50–84 of the original). The PHP tracker logic only runs when xbtt is disabled.

### 2.5 BitTorrent Scrape Endpoint

`scrape.php` is **198 lines** and is **currently absent from the plan** — it needs its own controller: `App\Http\Controllers\ScrapeController`.

**Same middleware exclusions as announce:** must skip `web` group (session, CSRF, cookies), auth, and rate limiting.

**Same xbtt redirect path** (lines 50–71) with the same `$pid` header injection — apply the same `/^[A-Za-z0-9]{1,64}$/` validation rule first.

**Multiscrape requires raw `QUERY_STRING` parsing — a non-obvious framework gotcha:**
- BitTorrent clients send `?info_hash=<20bytes>&info_hash=<20bytes>` (multiple values for the same key).
- PHP's `$_GET` and Laravel/Symfony's `$request->query()` both deduplicate to the last value only — the client's first N−1 hashes are silently dropped.
- The legacy code correctly parses `$_SERVER['QUERY_STRING']` manually (lines 124–158).
- **In the port**: parse `$request->server('QUERY_STRING')` manually in `ScrapeController`, looping over `&`-split segments and collecting every `info_hash=` occurrence. Do NOT use `$request->query('info_hash')`.

**Functional bug to fix, not replicate** (lines 138–143): the 40-character hex info_hash branch has both `if` and `else` both doing `continue` — 40-char hex hashes are always silently skipped regardless of validity. In the port: call `verifyHash()` (port as a static helper) and skip only on invalid input; accept valid 40-char hex hashes.

**Same `get_magic_quotes_gpc()` fatal** (line 150) — remove the dead `stripslashes()` branch entirely.

---

## Phase 3 — Frontend Modernization

### 3.1 Blade Templates
- Create `resources/views/layouts/app.blade.php` as base layout
- Port each bTemplate `.tpl` file to a Blade partial
- Blocks (`/blocks/*.php`) → Blade components (`resources/views/components/`)

### 3.2 Bootstrap 5 + HTML5
- Replace Bootstrap 3.3.7 → Bootstrap 5.x (via Vite + npm)
- Remove all `<table>`-based layouts → CSS flexbox/grid
- Replace XHTML 1.0 Transitional doctype → `<!DOCTYPE html>`
- Add `<meta name="viewport">` (missing — app is not mobile-responsive)
- Add semantic HTML5: `<nav>`, `<header>`, `<footer>`, `<main>`, `<article>`
- Remove deprecated HTML4 attributes: `cellpadding`, `cellspacing`, `align`, `valign`
- Remove IE-specific hacks: `pngfix.js`, conditional comments

### 3.3 JavaScript
- Remove jQuery 1.12.4 and SACK library (2005-era)
- Replace with vanilla ES6+ (`fetch()` API for AJAX)
- Use Vite (included with Laravel 11) for bundling

---

## Phase 4 — Remaining Features

> **Migration support note:** The Laravel app is built clean with no legacy compatibility shims. A one-shot `php artisan xbtit:import` command (reads the old `btit_*` schema, writes to the new one) will be built as a **separate future deliverable** once the new schema is stable — designing it before the schema settles means rewriting it multiple times. Plugin/addon (`/hacks/`, `/addons/`) compatibility is explicitly out of scope for the initial release; users with custom plugins will need to port them manually. The `LegacyPasswordVerifier` (Phase 1) and dual-column PID migration (Phase 1) are the only legacy-compatibility mechanisms built into the app itself.



- **Multi-language i18n**: Replace PHP array language files with Laravel's `lang/` system (`__('key')`)
- **Forum**: Keep internal forum as a Laravel module; drop SMF/IPB integration unless there is an active user base relying on it
- **Settings system**: Replace `btit_settings` global + file cache with `Setting` Eloquent model + `Cache::remember()`
- **RSS, polls, shoutbox**: Port as Blade components + controllers

---

## Target Laravel Project Structure

```
app/
  Http/
    Controllers/
      TorrentController.php
      AnnounceController.php
      ScrapeController.php
      UserController.php
      NewsController.php
      Admin/ConfigController.php
    Middleware/
      SecurityHeaders.php
      CheckIpBan.php
      EnsureAdmin.php
  Models/
    User.php, Torrent.php, Peer.php, News.php, Setting.php, BannedIp.php ...
  Services/
    BEncodeService.php        ← ported from include/BEncode.php + BDecode.php
    AnnounceService.php
    LegacyPasswordVerifier.php  ← handles all 6 legacy pass_type variants
    PasskeyService.php        ← generate, rotate, dual-column lookup, expiry logic
resources/views/
  layouts/app.blade.php
  torrents/index.blade.php, show.blade.php ...
  components/mainmenu.blade.php, news-block.blade.php ...
database/migrations/          ← one migration per table from sql/database.sql
routes/
  web.php
  announce.php                ← excluded from session + CSRF middleware
  scrape.php                  ← excluded from session + CSRF middleware
```

---

## Key Legacy Files to Reference During Porting

| Legacy File | Lines | Purpose |
|---|---|---|
| `announce.php` | 805 | Tracker announce — port to AnnounceService |
| `scrape.php` | 198 | Tracker scrape — port to ScrapeController; fix both-continue bug |
| `include/functions.php` | 1,975 | All utility functions — port selectively |
| `include/common.php` | ~1,081 | Bootstrap logic — replace with Laravel boot |
| `include/config.php` | 364 | Settings loading — replace with Setting model; contains 2 extra unserialize() calls (lines 48, 288) |
| `include/BEncode.php` + `BDecode.php` | — | Port to BEncodeService |
| `sql/database.sql` | — | Full schema source of truth for migrations |
| `include/functions.php:492–580` | — | Legacy auth/session logic to understand and replace |
| `include/functions.php:551,558` | — | Phase 0 unserialize patch target |

*Test strategy will be planned separately once Phase 1 scope is locked.*
