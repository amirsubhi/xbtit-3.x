@extends('layouts.app')

@section('title', $thread->title)

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">{{ __('forum.forum') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('forum.show', $thread->forum) }}">{{ $thread->forum->name }}</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($thread->title, 50) }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">
            @if($thread->sticky)
                <span class="badge bg-primary me-1"><i class="bi bi-pin-fill"></i></span>
            @endif
            @if($thread->locked)
                <span class="badge bg-secondary me-1"><i class="bi bi-lock-fill"></i></span>
            @endif
            {{ $thread->title }}
        </h5>
    </div>
    @auth
        @if(auth()->user()->isAdmin())
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('threads.sticky', $thread) }}">
                    @csrf
                    <button class="btn btn-sm {{ $thread->sticky ? 'btn-primary' : 'btn-outline-primary' }}">
                        <i class="bi bi-pin"></i> {{ $thread->sticky ? __('forum.unsticky') : __('forum.sticky') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('threads.lock', $thread) }}">
                    @csrf
                    <button class="btn btn-sm {{ $thread->locked ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        <i class="bi bi-lock"></i> {{ $thread->locked ? __('forum.unlock') : __('forum.lock') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('threads.destroy', $thread) }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('{{ __('forum.confirm_delete_thread') }}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        @endif
    @endauth
</div>

{{-- Posts --}}
@foreach($posts as $post)
    <div class="card mb-3" id="post-{{ $post->id }}">
        <div class="card-body">
            <div class="d-flex gap-3">
                {{-- Avatar / username sidebar --}}
                <div class="flex-shrink-0 text-center" style="width:90px">
                    @if($post->author?->avatar)
                        <img src="{{ $post->author->avatar }}" alt=""
                             class="rounded-circle mb-1" style="width:48px;height:48px;object-fit:cover">
                    @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center
                                    text-white fw-bold mx-auto mb-1"
                             style="width:48px;height:48px;font-size:1.2rem">
                            {{ strtoupper(substr($post->author?->username ?? '?', 0, 1)) }}
                        </div>
                    @endif
                    <div class="small fw-semibold">
                        <a href="{{ route('users.show', $post->author) }}" class="text-decoration-none">
                            {{ $post->author?->username ?? __('forum.deleted_user') }}
                        </a>
                    </div>
                    <div class="small text-muted">{{ $post->author?->level?->level_name ?? '' }}</div>
                </div>

                {{-- Post body --}}
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">{{ $post->created_at->format('d M Y, H:i') }}</small>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="#post-{{ $post->id }}" class="small text-muted">#{{ $post->id }}</a>
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('posts.destroy', $post) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger py-0 px-1"
                                                onclick="return confirm('{{ __('forum.confirm_delete_post') }}')"
                                                title="{{ __('forum.delete_post') }}">
                                            <i class="bi bi-trash" style="font-size:.75rem"></i>
                                        </button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                    <div>{!! nl2br(e($post->body)) !!}</div>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{ $posts->links('pagination::bootstrap-5') }}

{{-- Reply form --}}
@auth
    @if(!$thread->locked)
        <div class="card mt-4">
            <div class="card-header">{{ __('forum.post_reply') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('posts.store', $thread) }}">
                    @csrf
                    <div class="mb-3">
                        <textarea name="body" rows="5"
                                  class="form-control @error('body') is-invalid @enderror"
                                  placeholder="{{ __('forum.reply_placeholder') }}"
                                  required maxlength="10000">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> {{ __('forum.submit_reply') }}
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary mt-4">
            <i class="bi bi-lock-fill"></i> {{ __('forum.thread_locked_notice') }}
        </div>
    @endif
@else
    <div class="alert alert-info mt-4">
        <a href="{{ route('login') }}">{{ __('auth.login') }}</a> {{ __('forum.login_to_reply') }}
    </div>
@endauth
@endsection
