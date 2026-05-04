<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Torrent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, string $infoHash): RedirectResponse
    {
        $torrent = Torrent::where('info_hash', $infoHash)->firstOrFail();

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $uid = $request->user()->id;

        // One vote per user per torrent — update if exists, insert if not.
        Rating::updateOrCreate(
            ['info_hash' => $infoHash, 'uid' => $uid],
            ['rating' => $data['rating'], 'added' => time()]
        );

        return back()->with('status', 'Rating saved.');
    }
}
