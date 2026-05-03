@extends('layouts.app')

@section('title', __('news.news'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('news.news') }}</h4>
    @auth
        @if(auth()->user()->isAdmin())
            <a href="{{ route('news.create') }}" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg"></i> {{ __('news.post_news') }}
            </a>
        @endif
    @endauth
</div>

@forelse($news as $article)
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">
                <a href="{{ route('news.show', $article->id) }}" class="text-decoration-none">
                    {{ $article->title }}
                </a>
            </h5>
            <div class="text-muted small mb-2">
                {{ __('news.posted_by') }} <strong>{{ $article->author->username ?? 'Unknown' }}</strong>
                &middot; {{ $article->created_at?->format('d M Y') }}
            </div>
            <p class="card-text text-muted">{{ Str::limit(strip_tags($article->body), 200) }}</p>
            <a href="{{ route('news.show', $article->id) }}" class="btn btn-sm btn-outline-primary">
                {{ __('news.read_more') }}
            </a>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-5">{{ __('news.no_news') }}</div>
@endforelse

<div class="d-flex justify-content-center mt-3">
    {{ $news->links('pagination::bootstrap-5') }}
</div>
@endsection
