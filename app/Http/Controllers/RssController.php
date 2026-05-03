<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Torrent;

class RssController extends Controller
{
    public function torrents()
    {
        $torrents = Torrent::with('category')
            ->latest()
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
}
