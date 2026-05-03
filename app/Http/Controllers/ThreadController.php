<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThreadController extends Controller
{
    public function show(Thread $thread)
    {
        $thread->increment('views');

        $posts = $thread->posts()->with('author')->paginate(20);

        return view('thread.show', compact('thread', 'posts'));
    }

    public function create(Forum $forum)
    {
        return view('thread.create', compact('forum'));
    }

    public function store(Request $request, Forum $forum)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'body'  => ['required', 'string', 'min:3', 'max:10000'],
        ]);

        $thread = null;

        DB::transaction(function () use ($data, $forum, $request, &$thread) {
            $thread = Thread::create([
                'forum_id' => $forum->id,
                'user_id'  => $request->user()->id,
                'title'    => $data['title'],
            ]);

            Post::create([
                'thread_id' => $thread->id,
                'user_id'   => $request->user()->id,
                'body'      => $data['body'],
            ]);

            $now = now();
            $thread->update(['last_post_at' => $now]);
            $forum->increment('thread_count');
            $forum->increment('post_count');
            $forum->update(['last_post_at' => $now]);
        });

        return redirect()->route('threads.show', $thread)
            ->with('status', __('forum.thread_created'));
    }

    public function lock(Thread $thread)
    {
        $thread->update(['locked' => !$thread->locked]);

        return back()->with('status', $thread->locked ? __('forum.thread_locked') : __('forum.thread_unlocked'));
    }

    public function sticky(Thread $thread)
    {
        $thread->update(['sticky' => !$thread->sticky]);

        return back()->with('status', $thread->sticky ? __('forum.thread_stickied') : __('forum.thread_unstickied'));
    }

    public function destroy(Thread $thread)
    {
        $forum = $thread->forum;
        $replyCount = $thread->reply_count;

        DB::transaction(function () use ($thread, $forum, $replyCount) {
            $thread->delete();
            $forum->decrement('thread_count');
            $forum->decrement('post_count', $replyCount + 1);
        });

        return redirect()->route('forum.show', $forum)
            ->with('status', __('forum.thread_deleted'));
    }
}
