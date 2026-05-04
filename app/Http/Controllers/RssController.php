<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Thread;
use App\Models\Torrent;
use Illuminate\Support\Collection;

class RssController extends Controller
{
    public function torrents()
    {
        $torrents = Torrent::with('category')
            ->orderByDesc('added')
            ->limit(50)
            ->get();

        return response()
            ->view('rss.torrents', compact('torrents'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function news()
    {
        $articles = News::with('author')
            ->latest()
            ->limit(20)
            ->get();

        return response()
            ->view('rss.news', compact('articles'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * Combined feed: latest torrents + latest forum posts merged by date (C-30).
     */
    public function combined()
    {
        $torrents = Torrent::with('category')
            ->orderByDesc('added')
            ->limit(25)
            ->get()
            ->map(fn ($t) => [
                'type'       => 'torrent',
                'title'      => $t->filename,
                'link_key'   => $t->info_hash,
                'date'       => $t->added,
                'category'   => $t->category?->name ?? '',
                'description'=> $t->info,
            ]);

        $threads = Thread::with('forum', 'author')
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($th) => [
                'type'       => 'thread',
                'title'      => $th->title,
                'link_key'   => $th->id,
                'date'       => $th->created_at->timestamp,
                'category'   => $th->forum?->name ?? 'Forum',
                'description'=> '',
            ]);

        $items = $torrents->concat($threads)
            ->sortByDesc('date')
            ->take(50)
            ->values();

        return response()
            ->view('rss.combined', compact('items'))
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
