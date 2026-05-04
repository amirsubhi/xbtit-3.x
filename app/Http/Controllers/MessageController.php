<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function inbox(Request $request): View
    {
        $messages = Message::where('receiver_id', $request->user()->id)
            ->where('receiver_deleted', false)
            ->with('sender')
            ->latest()
            ->paginate(20);

        return view('messages.inbox', compact('messages'));
    }

    public function sent(Request $request): View
    {
        $messages = Message::where('sender_id', $request->user()->id)
            ->where('sender_deleted', false)
            ->with('receiver')
            ->latest()
            ->paginate(20);

        return view('messages.sent', compact('messages'));
    }

    public function show(Request $request, Message $message): View
    {
        $this->authorizeView($request->user(), $message);

        if ($message->receiver_id === $request->user()->id && !$message->isRead()) {
            $message->update(['read_at' => now()]);
        }

        return view('messages.show', compact('message'));
    }

    public function create(Request $request): View
    {
        $to = $request->query('to')
            ? User::find((int) $request->query('to'))
            : null;

        return view('messages.create', compact('to'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'subject'     => ['required', 'string', 'max:255'],
            'body'        => ['required', 'string', 'max:10000'],
        ]);

        if ((int) $data['receiver_id'] === $request->user()->id) {
            return back()->withErrors(['receiver_id' => 'You cannot send a message to yourself.']);
        }

        Message::create([
            'sender_id'   => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'subject'     => $data['subject'],
            'body'        => $data['body'],
        ]);

        return redirect()->route('messages.sent')->with('status', 'Message sent.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        $uid = $request->user()->id;

        if ($message->sender_id === $uid) {
            $message->update(['sender_deleted' => true]);
        } elseif ($message->receiver_id === $uid) {
            $message->update(['receiver_deleted' => true]);
        } else {
            abort(403);
        }

        // Hard-delete only when both sides have deleted.
        if ($message->fresh()->sender_deleted && $message->fresh()->receiver_deleted) {
            $message->delete();
        }

        return back()->with('status', 'Message deleted.');
    }

    private function authorizeView(User $user, Message $message): void
    {
        if ($message->sender_id !== $user->id && $message->receiver_id !== $user->id) {
            abort(403);
        }
    }
}
