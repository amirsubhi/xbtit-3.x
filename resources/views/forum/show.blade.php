@extends('layouts.app')

@section('title', $forum->name)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">{{ __('forum.forum') }}</a></li>
        <li class="breadcrumb-item active">{{ $forum->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">{{ $forum->name }}</h5>
    @auth
        <a href="{{ route('threads.create', $forum) }}" class="btn btn-sm btn-success">
            <i class="bi bi-plus-lg"></i> {{ __('forum.new_thread') }}
        </a>
    @endauth
</div>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th>{{ __('forum.thread') }}</th>
                <th class="text-center" style="width:80px">{{ __('forum.replies') }}</th>
                <th class="text-center" style="width:70px">{{ __('forum.views') }}</th>
                <th class="text-end" style="width:120px">{{ __('forum.last_post') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($threads as $thread)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if($thread->sticky)
                                <span class="badge bg-primary" title="{{ __('forum.sticky') }}">
                                    <i class="bi bi-pin-fill"></i>
                                </span>
                            @endif
                            @if($thread->locked)
                                <span class="badge bg-secondary" title="{{ __('forum.locked') }}">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                            @endif
                            <div>
                                <a href="{{ route('threads.show', $thread) }}"
                                   class="text-decoration-none fw-semibold">
                                    {{ $thread->title }}
                                </a>
                                <div class="small text-muted">
                                    {{ __('forum.by') }} {{ $thread->author->username ?? '—' }}
                                    &middot; {{ $thread->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-center text-muted small">{{ $thread->reply_count }}</td>
                    <td class="text-center text-muted small">{{ number_format($thread->views) }}</td>
                    <td class="text-end text-muted small">
                        {{ $thread->last_post_at?->diffForHumans() ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ __('forum.no_threads') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $threads->links('pagination::bootstrap-5') }}
@endsection
