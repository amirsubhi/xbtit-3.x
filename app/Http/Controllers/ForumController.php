<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\ForumCategory;

class ForumController extends Controller
{
    public function index()
    {
        $categories = ForumCategory::with(['forums' => function ($q) {
            $q->orderBy('display_order');
        }])->orderBy('display_order')->get();

        return view('forum.index', compact('categories'));
    }

    public function show(Forum $forum)
    {
        $threads = $forum->threads()
            ->with('author')
            ->orderByDesc('sticky')
            ->orderByDesc('last_post_at')
            ->paginate(25);

        return view('forum.show', compact('forum', 'threads'));
    }
}
