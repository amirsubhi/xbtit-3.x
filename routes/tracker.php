<?php

/**
 * BitTorrent tracker endpoints — no web middleware (no session, CSRF, cookies, auth).
 * CheckIpBan and rate limiting are handled inside AnnounceService/ScrapeController directly.
 */

use App\Http\Controllers\AnnounceController;
use App\Http\Controllers\ScrapeController;
use Illuminate\Support\Facades\Route;

// Legacy URL paths baked into existing .torrent files — both must work
Route::get('/announce.php', [AnnounceController::class, 'handle']);
Route::get('/announce',     [AnnounceController::class, 'handle']);

Route::get('/scrape.php', [ScrapeController::class, 'handle']);
Route::get('/scrape',     [ScrapeController::class, 'handle']);
