<?php

namespace App\Http\Controllers;

use App\Models\Shout;
use Illuminate\Http\Request;

class ShoutController extends Controller
{
    /** Return the latest shouts as JSON (polled every 3 s). */
    public function index(Request $request)
    {
        $since = $request->query('since');

        $query = Shout::with('author:id,username')
            ->orderByDesc('id')
            ->limit(30);

        if ($since) {
            $query->where('id', '>', (int) $since);
        }

        $shouts = $query->get()->reverse()->values();

        return response()->json($shouts);
    }

    /** Post a new shout (auth required). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $shout = Shout::create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        $shout->load('author:id,username');

        return response()->json($shout, 201);
    }

    /** Admin delete. */
    public function destroy(Shout $shout)
    {
        $shout->delete();

        return response()->json(['ok' => true]);
    }
}
