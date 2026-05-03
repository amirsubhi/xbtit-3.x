@extends('layouts.app')

@section('title', $article->title)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('news.index') }}">{{ __('news.news') }}</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($article->title, 50) }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ $article->title }}</h3>
        @auth
            @if(auth()->user()->isAdmin())
                <a href="{{ route('news.edit', $article->id) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endif
        @endauth
    </div>
    <div class="card-body">
        <p class="text-muted small mb-4">
            {{ __('news.posted_by') }} <strong>{{ $article->author->username ?? 'Unknown' }}</strong>
            &middot; {{ $article->created_at?->format('d M Y, H:i') }}
        </p>
        <div class="card-text" style="white-space: pre-wrap;">{{ $article->body }}</div>
    </div>
</div>
@endsection
