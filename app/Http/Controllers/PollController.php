<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollVote;
use Illuminate\Http\Request;

class PollController extends Controller
{
    public function vote(Request $request, Poll $poll)
    {
        $data = $request->validate([
            'option_id' => ['required', 'integer', 'exists:poll_options,id'],
        ]);

        if (!$poll->active) {
            return back()->with('error', __('polls.poll_closed'));
        }

        if ($poll->userHasVoted($request->user()->id)) {
            return back()->with('error', __('polls.already_voted'));
        }

        PollVote::create([
            'poll_id'        => $poll->id,
            'poll_option_id' => $data['option_id'],
            'user_id'        => $request->user()->id,
        ]);

        return back()->with('status', __('polls.vote_recorded'));
    }
}
