# xbtit v4 — Laravel Rewrite

A full rewrite of the [xbtit BitTorrent tracker](https://github.com/BTITeam/xbtit-3.x) built on **Laravel 13**, PHP 8.2+, Bootstrap 5, and Tailwind CSS. All core tracker functionality is preserved with a modern architecture, security hardening, and maintainable code.

## Origin & Attribution

This project is a refresh of **xbtit 3.x** by the [BTITeam](https://github.com/BTITeam/xbtit-3.x), which itself evolved from BtiTracker and earlier PHP tracker work. Full lineage is preserved in the git history.

Original credits from xbtit 3.x:
- **Founder:** Lupin · **Owner:** King Cobra — BTITeam
- xbtt C++ tracker backend: Olaf van der Spek
- phpmailer, SMF, bTemplate, and the broader tracker community

This rewrite is released under the same modified BSD licence. Third-party dependency credits are listed in `LICENSE.txt`.

## What Changed in v4

| Area | xbtit 3.x | v4 |
|---|---|---|
| Framework | Raw PHP, no routing | Laravel 13 (MVC, routing, Eloquent) |
| Auth | MD5/SHA1 passwords, raw sessions | Breeze + bcrypt/argon2 + CSRF |
| Templates | bTemplate custom engine | Blade + Bootstrap 5 + Tailwind CSS |
| DB access | Raw MySQLi queries | Eloquent ORM + query builder |
| Announce | announce.php monolith | `AnnounceService` with injected dependencies |
| Security | Unserialize RCE, XSS, SQL-i | Prepared statements, policies, validated input |
| Admin | 27 flat PHP admin files | Resourceful admin controllers + Blade views |
| Frontend | 5 bTemplate themes | 3 Blade themes (xbtit-default, darklair, modern) |
| i18n | 25+ PHP array language files | Laravel `lang/` structure (en, es, zh, ar, fr, pt, ms) |

## Features

- BitTorrent announce & scrape (PHP tracker; xbtt C++ backend supported via passkey redirect)
- Torrent upload, browse, search, download with passkey-injected announce URLs
- User registration, login, account settings, passkey management
- Admin panel: users, categories, torrents, IP bans, site settings
- Internal forum with categories, sub-forums, threads, replies, lock/sticky/delete
- News posts with comments
- Shoutbox (AJAX polling)
- Polls
- RSS feeds (torrents, news)
- Multi-language support: English, Spanish, Chinese, Arabic, French, Portuguese, Malay
- Multi-theme support: xbtit-default, darklair, modern

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.4+
- Composer 2
- Node.js 18+ and npm

## Setup

```bash
git clone https://github.com/amirsubhi/xbtit-3.x.git -b v4 xbtit
cd xbtit
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials, then:

```bash
php artisan migrate
php artisan db:seed
npm run build
```

## Development

Run the local dev server with hot-reloading:

```bash
# In one terminal
php artisan serve

# In another terminal
npm run dev
```

## Tracker Announce URL

```
https://your-tracker.com/announce/{passkey}
```

Passkeys are generated per user on registration and can be regenerated from the account page.

## License

Copyright (C) 2004–2010 Btiteam  
Copyright (C) 2024–2026 Amir Subhi

Released under the BSD 3-Clause License. See `LICENSE.txt` for the full license text and third-party credits.
