<?php

use App\Http\Controllers\Admin\BanController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ForumCategoryController as AdminForumController;
use App\Http\Controllers\Admin\PollController as AdminPollController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TorrentController as AdminTorrentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RssController;
use App\Http\Controllers\ShoutController;
use App\Http\Controllers\ThreadController;
use App\Http\Controllers\TorrentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TorrentController::class, 'index'])->name('home');

// Auth routes (Breeze)
require __DIR__.'/auth.php';

// Public torrent & news routes
Route::get('/torrents',                [TorrentController::class, 'index'])->name('torrents.index');
Route::get('/torrents/{infoHash}',     [TorrentController::class, 'show'])->name('torrents.show');
Route::get('/details/{infoHash}',      [TorrentController::class, 'show']);  // legacy URL alias

Route::get('/news',       [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{id}',  [NewsController::class, 'show'])->name('news.show');

// RSS feeds (public)
Route::get('/rss/torrents', [RssController::class, 'torrents'])->name('rss.torrents');
Route::get('/rss/news',     [RssController::class, 'news'])->name('rss.news');

// Shoutbox — public poll, auth post, admin delete
Route::get('/shoutbox',  [ShoutController::class, 'index'])->name('shoutbox.index');
Route::middleware('auth')->group(function () {
    Route::post('/shoutbox',           [ShoutController::class, 'store'])->name('shoutbox.store');
    Route::middleware('admin')->delete('/shoutbox/{shout}', [ShoutController::class, 'destroy'])->name('shoutbox.destroy');
});

// Forum (public read, auth to post)
Route::get('/forum',                  [ForumController::class, 'index'])->name('forum.index');
Route::get('/forum/{forum}',          [ForumController::class, 'show'])->name('forum.show');
Route::get('/thread/{thread}',        [ThreadController::class, 'show'])->name('threads.show');

// Poll vote (auth required)
Route::middleware('auth')->post('/polls/{poll}/vote', [PollController::class, 'vote'])->name('polls.vote');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // Torrents
    Route::get('/upload',                          [TorrentController::class, 'create'])->name('torrents.create');
    Route::post('/upload',                         [TorrentController::class, 'store'])->name('torrents.store');
    Route::get('/download/{infoHash}',             [TorrentController::class, 'download'])->name('torrents.download');
    Route::get('/torrents/{infoHash}/edit',        [TorrentController::class, 'edit'])->name('torrents.edit');
    Route::patch('/torrents/{infoHash}',           [TorrentController::class, 'update'])->name('torrents.update');
    Route::delete('/torrents/{infoHash}',          [TorrentController::class, 'destroy'])->name('torrents.destroy');

    // Torrent comments
    Route::post('/torrents/{infoHash}/comments',   [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}',           [CommentController::class, 'destroy'])->name('comments.destroy');

    // User profile & account
    Route::get('/users/{id}',      [UserController::class, 'show'])->name('users.show');
    Route::get('/account',         [UserController::class, 'edit'])->name('account.edit');
    Route::patch('/account',       [UserController::class, 'update'])->name('account.update');
    Route::post('/account/passkey',[UserController::class, 'regeneratePasskey'])->name('passkey.regenerate');

    // Breeze profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Forum — authenticated actions
    Route::get('/forum/{forum}/new',         [ThreadController::class, 'create'])->name('threads.create');
    Route::post('/forum/{forum}/new',        [ThreadController::class, 'store'])->name('threads.store');
    Route::post('/thread/{thread}/reply',    [PostController::class,   'store'])->name('posts.store');

    // Forum moderation (admin only)
    Route::middleware('admin')->group(function () {
        Route::post('/thread/{thread}/lock',   [ThreadController::class, 'lock'])->name('threads.lock');
        Route::post('/thread/{thread}/sticky', [ThreadController::class, 'sticky'])->name('threads.sticky');
        Route::delete('/thread/{thread}',      [ThreadController::class, 'destroy'])->name('threads.destroy');
        Route::delete('/post/{post}',          [PostController::class,   'destroy'])->name('posts.destroy');
    });

    // News management (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/news/create',    [NewsController::class, 'create'])->name('news.create');
        Route::post('/news',          [NewsController::class, 'store'])->name('news.store');
        Route::get('/news/{id}/edit', [NewsController::class, 'edit'])->name('news.edit');
        Route::patch('/news/{id}',    [NewsController::class, 'update'])->name('news.update');
        Route::delete('/news/{id}',   [NewsController::class, 'destroy'])->name('news.destroy');
    });
});

// Admin panel
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('admin.dashboard'))->name('dashboard');

    // Settings
    Route::get('/settings',   [SettingController::class, 'index'])->name('settings.index');
    Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Forum management
    Route::get('/forum',                                           [AdminForumController::class, 'index'])->name('forum.index');
    Route::post('/forum/categories',                               [AdminForumController::class, 'storeCategory'])->name('forum.category.store');
    Route::delete('/forum/categories/{category}',                  [AdminForumController::class, 'destroyCategory'])->name('forum.category.destroy');
    Route::post('/forum/categories/{category}/forums',             [AdminForumController::class, 'storeForum'])->name('forum.forum.store');
    Route::delete('/forum/forums/{forum}',                         [AdminForumController::class, 'destroyForum'])->name('forum.forum.destroy');

    // Poll management
    Route::get('/polls',                  [AdminPollController::class, 'index'])->name('polls.index');
    Route::get('/polls/create',           [AdminPollController::class, 'create'])->name('polls.create');
    Route::post('/polls',                 [AdminPollController::class, 'store'])->name('polls.store');
    Route::post('/polls/{poll}/activate', [AdminPollController::class, 'activate'])->name('polls.activate');
    Route::delete('/polls/{poll}',        [AdminPollController::class, 'destroy'])->name('polls.destroy');

    // Category management
    Route::get('/categories',             [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories',            [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // User management
    Route::get('/users',                  [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}',           [AdminUserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}',         [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');

    // Torrent management
    Route::get('/torrents',               [AdminTorrentController::class, 'index'])->name('torrents.index');
    Route::get('/torrents/{torrent}/edit',[AdminTorrentController::class, 'edit'])->name('torrents.edit');
    Route::patch('/torrents/{torrent}',   [AdminTorrentController::class, 'update'])->name('torrents.update');
    Route::delete('/torrents/{torrent}',  [AdminTorrentController::class, 'destroy'])->name('torrents.destroy');

    // IP ban management
    Route::get('/bans',                   [BanController::class, 'index'])->name('bans.index');
    Route::post('/bans',                  [BanController::class, 'store'])->name('bans.store');
    Route::delete('/bans/{ban}',          [BanController::class, 'destroy'])->name('bans.destroy');
});
