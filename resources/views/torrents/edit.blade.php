@extends('layouts.app')

@section('title', __('torrents.edit_title'))

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('torrents.index') }}">{{ __('torrents.torrents') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('torrents.show', $torrent->info_hash) }}">{{ Str::limit($torrent->filename, 40) }}</a></li>
        <li class="breadcrumb-item active">{{ __('torrents.edit_title') }}</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">{{ __('torrents.edit_title') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('torrents.update', $torrent->info_hash) }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="filename" class="form-label">{{ __('torrents.name') }}</label>
                        <input type="text" name="filename" id="filename" class="form-control @error('filename') is-invalid @enderror"
                               value="{{ old('filename', $torrent->filename) }}" required maxlength="255">
                        @error('filename')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">{{ __('torrents.category') }}</label>
                        <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">— {{ __('torrents.category') }} —</option>
                            @foreach($categories as $parent)
                                @if($parent->children->isNotEmpty())
                                    <optgroup label="{{ $parent->name }}">
                                        @foreach($parent->children as $child)
                                            <option value="{{ $child->id }}" @selected(old('category', $torrent->category) == $child->id)>
                                                {{ $child->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @else
                                    <option value="{{ $parent->id }}" @selected(old('category', $torrent->category) == $parent->id)>
                                        {{ $parent->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="info" class="form-label">{{ __('torrents.description') }}</label>
                        <textarea name="info" id="info" rows="6" class="form-control @error('info') is-invalid @enderror"
                                  maxlength="5000">{{ old('info', $torrent->info) }}</textarea>
                        @error('info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('torrents.save') }}</button>
                        <a href="{{ route('torrents.show', $torrent->info_hash) }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
