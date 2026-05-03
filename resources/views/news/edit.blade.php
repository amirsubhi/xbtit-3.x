@extends('layouts.app')

@section('title', 'Edit: ' . $article->title)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Edit Article</h5>
                <form method="POST" action="{{ route('news.destroy', $article->id) }}"
                      onsubmit="return confirm('Delete this article permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('news.update', $article->id) }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input id="title" type="text" name="title" maxlength="100"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $article->title) }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label">Body <span class="text-danger">*</span></label>
                        <textarea id="body" name="body" rows="12"
                                  class="form-control @error('body') is-invalid @enderror"
                                  required>{{ old('body', $article->body) }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('news.show', $article->id) }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
