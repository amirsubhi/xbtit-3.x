# xbtit v4 — Laravel Rewrite

A full rewrite of the [xbtit BitTorrent tracker](https://github.com/BTITeam/xbtit-3.x) built on **Laravel 13**, PHP 8.3, and Bootstrap 5. All core tracker functionality is preserved with a modern architecture, security hardening, and maintainable code.

## Origin & Attribution

This project is a refresh of **xbtit 3.x** by the [BTITeam](https://github.com/BTITeam/xbtit-3.x), which itself evolved from BtiTracker and earlier PHP tracker work. Full lineage is preserved in the git history.

Original credits from xbtit 3.x:
- **Founder:** Lupin · **Owner:** King Cobra — BTITeam
- xbtt C++ tracker backend: Olaf van der Spek
- phpmailer, SMF, bTemplate, and the broader tracker community

This rewrite is released under the same modified BSD licence — see `LICENSE.txt`.

## What Changed in v4

| Area | xbtit 3.x | v4 |
|---|---|---|
| Framework | Raw PHP, no routing | Laravel 13 (MVC, routing, Eloquent) |
| Auth | MD5/SHA1 passwords, raw sessions | Breeze + bcrypt/argon2 + CSRF |
| Templates | bTemplate custom engine | Blade + Bootstrap 5 |
| DB access | Raw MySQLi queries | Eloquent ORM + query builder |
| Announce | announce.php monolith | `AnnounceService` with injected dependencies |
| Security | Unserialize RCE, XSS, SQL-i | Prepared statements, policies, validated input |
| Admin | 27 flat PHP admin files | Resourceful admin controllers + Blade views |

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
- Multi-language support (Laravel `lang/` structure)

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.4+
- Composer 2

## Setup

```bash
git clone https://github.com/amirsubhi/xbtit-3.x.git -b v4 xbtit
cd xbtit
composer install
cp .env.example .env
php artisan key:generate
# Edit .env with DB credentials
php artisan migrate
php artisan db:seed
```

## Tracker Announce URL

```
https://your-tracker.com/announce/{passkey}
```

Passkeys are generated per user on registration and can be regenerated from the account page.
