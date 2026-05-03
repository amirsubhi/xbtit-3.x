# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**xbtit** is a PHP-based BitTorrent tracker and web frontend (version 2.6.1). It handles torrent indexing, peer tracking via the BitTorrent announce protocol, user management, and an optional internal forum. It supports an optional high-performance C++ backend (`xbtt`).

- **Language:** PHP 7.2+
- **Database:** MySQL 5.6+ via MySQLi (no ORM)
- **Web Server:** Apache or Lighttpd
- **Template Engine:** bTemplate (custom — not Twig or Blade)
- **Dependency Manager:** Composer (one dependency: `splitbrain/php-archive`)

## Setup

### Install
1. Deploy to a PHP-capable web root
2. Create a MySQL database (UTF-8 Unicode)
3. Open `install.php` in a browser and follow the wizard (credentials → schema import → tracker settings)
4. Delete `install.php` and `install.unlock` after completion

### Composer
```
composer install
```

### Configuration
There is no `.env` file. All configuration is database-driven, stored in the `btit_settings` table and cached to the filesystem. Database credentials are written to `include/settings.php` by the installer.

## Architecture

### Request Flow

The application has no MVC routing layer. Each root-level PHP file is a standalone endpoint:

| File | Purpose |
|------|---------|
| `index.php` | Main torrent listing / frontend |
| `announce.php` | BitTorrent tracker announce endpoint (peer registration) |
| `details.php` | Torrent detail page |
| `upload.php` | Torrent upload |
| `account.php` / `login.php` / `logout.php` | Authentication |
| `install.php` | One-time installation wizard |

Each page bootstraps by including `include/common.php`, which sets up the database connection, loads config from cache, starts the session (`xbtit`), and initializes the template engine.

### Core Includes (`include/`)

- **`common.php`** — bootstrap: DB connect, config load, session start, template init
- **`functions.php`** (~1,975 lines) — all shared utility functions; no classes
- **`config.php`** — database connection and tracker config constants
- **`settings.php`** — DB credentials (written by installer, not committed)
- **`BEncode.php` / `BDecode.php`** — BitTorrent bencode encoding/decoding
- **`crk_protection.php`** — XSS/SQL injection protection with attack logging
- **`security_code.php`** — security helper functions
- **`class.*.php`** — utility classes (AJAX poll, BBCode parser, captcha, RSS reader, archive)

### Template System (bTemplate)

Templates live in `/style/{theme}/` (5 themes: `xbtit_default`, `darklair`, `frosted`, `mintgreen`, `thehive`). bTemplate uses its own tag delimiters — not PHP or Twig syntax. Page blocks are assembled from `/blocks/` (22 display block files: news, polls, shoutbox, forum summary, etc.).

### Admin Panel (`admin/`)

27 modules accessed at `admin/index.php`. Handles: global config, user/group management, IP bans, category management, forum management, style/language management, hack installation, database utilities, logs, and more.

### Multi-language Support

25+ language packs in `/language/`. Strings are PHP arrays (not constants or gettext). The active language file is loaded during bootstrap.

### Database Pattern

No ORM or query builder. Raw MySQLi throughout. All queries use the global DB connection initialized in `common.php`. Table names use a configurable prefix (default `btit_`). The full schema is in `sql/database.sql`.

### Hack/Addon System

`/hacks/` contains a one-click installer system for modular modifications. `/addons/` contains additional modules (clock, server load display). Prefer using this system over patching core files when adding features.

### Optional xbtt C++ Backend

For high-load deployments, the PHP `announce.php` can be replaced by the `xbtt` C++ tracker daemon. When enabled (via admin panel), it shares the same MySQL database. Import `xbt_tracker.sql` and configure `xbt_tracker.conf` to use it.

### Optional Forum Integrations

- **Internal forum:** `/forum/` directory
- **SMF:** Place SMF in `./smf/`; use `smf_import.php` for migration
- **IPB (Invision Power Board):** Schema in `sql/ipb.sql`
