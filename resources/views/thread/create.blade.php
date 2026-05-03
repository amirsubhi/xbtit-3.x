@extends('layouts.app')

@section('title', __('forum.new_thread'))

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('forum.index') }}">{{ __('forum.forum') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('forum.show', $forum) }}">{{ $forum->name }}</a></li>
        <li class="breadcrumb-item active">{{ __('forum.new_thread') }}</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('forum.new_thread') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('threads.store', $forum) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">
                            {{ __('forum.thread_title') }} <span class="text-danger">*</span>
                        </label>
                        <input id="title" type="text" name="title" maxlength="200"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">
                            {{ __('forum.post_body') }} <span class="text-danger">*</span>
                        </label>
                        <textarea id="body" name="body" rows="8"
                                  class="form-control @error('body') is-invalid @enderror"
                                  required maxlength="10000">{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('forum.show', $forum) }}" class="btn btn-outline-secondary">
                            {{ __('forum.cancel') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> {{ __('forum.submit_thread') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
