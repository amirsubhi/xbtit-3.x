@extends('layouts.app')

@section('title', 'Edit Torrent — Admin')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.torrents.index') }}">Torrents</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($torrent->filename, 40) }}</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Edit Torrent</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.torrents.update', $torrent->info_hash) }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Filename</label>
                        <input type="text" name="filename" class="form-control @error('filename') is-invalid @enderror"
                               value="{{ old('filename', $torrent->filename) }}" required maxlength="255">
                        @error('filename')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">— Select —</option>
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
                        <label class="form-label">Description</label>
                        <textarea name="info" rows="6" class="form-control @error('info') is-invalid @enderror"
                                  maxlength="5000">{{ old('info', $torrent->info) }}</textarea>
                        @error('info')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-2">
                        <small class="text-muted">Info hash: <code>{{ $torrent->info_hash }}</code></small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('admin.torrents.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
