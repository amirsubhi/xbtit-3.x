@extends('layouts.app')

@section('title', __('forum.forum'))

@section('content')
<h4 class="mb-4">{{ __('forum.forum') }}</h4>

@forelse($categories as $category)
    <div class="card mb-4">
        <div class="card-header fw-semibold">{{ $category->name }}</div>
        <div class="list-group list-group-flush">
            @foreach($category->forums as $forum)
                <a href="{{ route('forum.show', $forum) }}"
                   class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $forum->name }}</div>
                            @if($forum->description)
                                <div class="small text-muted">{{ $forum->description }}</div>
                            @endif
                        </div>
                        <div class="text-end small text-muted ms-3 flex-shrink-0">
                            <div>{{ number_format($forum->thread_count) }} {{ __('forum.threads') }}</div>
                            <div>{{ number_format($forum->post_count) }} {{ __('forum.posts') }}</div>
                            @if($forum->last_post_at)
                                <div class="text-muted">{{ $forum->last_post_at->diffForHumans() }}</div>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@empty
    <div class="text-center text-muted py-5">{{ __('forum.no_forums') }}</div>
@endforelse
@endsection
