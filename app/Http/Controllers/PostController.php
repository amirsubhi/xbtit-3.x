<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function store(Request $request, Thread $thread)
    {
        if ($thread->locked) {
            return back()->with('error', __('forum.thread_locked_reply'));
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:10000'],
        ]);

        DB::transaction(function () use ($data, $thread, $request) {
            Post::create([
                'thread_id' => $thread->id,
                'user_id'   => $request->user()->id,
                'body'      => $data['body'],
            ]);

            $now = now();
            $thread->increment('reply_count');
            $thread->update(['last_post_at' => $now]);
            $thread->forum->increment('post_count');
            $thread->forum->update(['last_post_at' => $now]);
        });

        $lastPage = (int) ceil(($thread->fresh()->reply_count + 1) / 20);

        return redirect()->route('threads.show', [$thread, 'page' => $lastPage])
            ->with('status', __('forum.reply_posted'));
    }

    public function destroy(Post $post)
    {
        $thread = $post->thread;

        // Don't allow deleting the opening post; delete the whole thread instead
        if ($post->id === $thread->posts()->min('id')) {
            return back()->with('error', __('forum.cannot_delete_opening_post'));
        }

        DB::transaction(function () use ($post, $thread) {
            $post->delete();
            $thread->decrement('reply_count');
            $thread->forum->decrement('post_count');
        });

        return back()->with('status', __('forum.post_deleted'));
    }
}
