<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Torrent;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, string $infoHash)
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        Comment::create([
            'info_hash' => $torrent->info_hash,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
            'ori_text'  => $data['body'],
        ]);

        return back()->with('status', __('torrents.comment_posted'));
    }

    public function destroy(Comment $comment)
    {
        $infoHash = $comment->info_hash;
        $comment->delete();

        return redirect()->route('torrents.show', $infoHash)
            ->with('status', __('torrents.comment_deleted'));
    }
}
