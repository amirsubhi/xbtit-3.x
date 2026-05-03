@extends('layouts.app')

@section('title', __('news.create_title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('news.create_title') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('news.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">{{ __('news.title') }} <span class="text-danger">*</span></label>
                        <input id="title" type="text" name="title" maxlength="100"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required autofocus>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">{{ __('news.body') }} <span class="text-danger">*</span></label>
                        <textarea id="body" name="body" rows="10"
                                  class="form-control @error('body') is-invalid @enderror"
                                  required>{{ old('body') }}</textarea>
                        @error('body')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('news.index') }}" class="btn btn-outline-secondary">{{ __('news.news') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('news.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
