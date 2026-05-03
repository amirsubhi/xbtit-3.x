@extends('layouts.app')

@section('title', 'Poll Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Poll Management</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.polls.create') }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg"></i> New Poll
        </a>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th class="text-center">Votes</th>
                    <th class="text-center">Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($polls as $poll)
                    <tr>
                        <td>{{ $poll->title }}</td>
                        <td class="text-center">{{ $poll->votes_count }}</td>
                        <td class="text-center">
                            @if($poll->active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $poll->created_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @unless($poll->active)
                                    <form method="POST" action="{{ route('admin.polls.activate', $poll) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Activate</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('admin.polls.destroy', $poll) }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this poll?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No polls yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
