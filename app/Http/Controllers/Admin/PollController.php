<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function index()
    {
        $polls = Poll::with('author')->withCount('votes')->latest()->get();
        return view('admin.polls.index', compact('polls'));
    }

    public function create()
    {
        return view('admin.polls.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'active'   => ['boolean'],
            'options'  => ['required', 'array', 'min:2', 'max:10'],
            'options.*'=> ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request) {
            if (!empty($data['active'])) {
                Poll::where('active', true)->update(['active' => false]);
            }

            $poll = Poll::create([
                'user_id' => $request->user()->id,
                'title'   => $data['title'],
                'active'  => !empty($data['active']),
            ]);

            foreach ($data['options'] as $i => $text) {
                if (trim($text) === '') continue;
                PollOption::create([
                    'poll_id'       => $poll->id,
                    'text'          => trim($text),
                    'display_order' => $i,
                ]);
            }
        });

        return redirect()->route('admin.polls.index')->with('status', __('polls.created'));
    }

    public function activate(Poll $poll)
    {
        Poll::where('active', true)->update(['active' => false]);
        $poll->update(['active' => true]);

        return back()->with('status', __('polls.activated'));
    }

    public function destroy(Poll $poll)
    {
        $poll->delete();
        return back()->with('status', __('polls.deleted'));
    }
}
