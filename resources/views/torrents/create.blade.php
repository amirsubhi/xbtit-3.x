@extends('layouts.app')

@section('title', __('torrents.upload_title'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('torrents.upload_title') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('torrents.store') }}" enctype="multipart/form-data"
                      id="uploadForm">
                    @csrf

                    <div class="mb-3">
                        <label for="torrent" class="form-label">{{ __('torrents.torrent_file') }} <span class="text-danger">*</span></label>
                        <input id="torrent" type="file" name="torrent" accept=".torrent"
                               class="form-control @error('torrent') is-invalid @enderror" required>
                        @error('torrent')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">{{ __('torrents.category') }} <span class="text-danger">*</span></label>
                        <select id="category" name="category"
                                class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">Select a category…</option>
                            @foreach($categories as $cat)
                                <optgroup label="{{ $cat->name }}">
                                    @foreach($cat->children as $sub)
                                        <option value="{{ $sub->id }}"
                                            {{ old('category') == $sub->id ? 'selected' : '' }}>
                                            {{ $sub->name }}
                                        </option>
                                    @endforeach
                                    @if($cat->children->isEmpty())
                                        <option value="{{ $cat->id }}"
                                            {{ old('category') == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->name }}
                                        </option>
                                    @endif
                                </optgroup>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">{{ __('torrents.description') }} <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="6"
                                  class="form-control @error('description') is-invalid @enderror"
                                  required maxlength="2000">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Max 2000 characters.</div>
                    </div>

                    <div class="mb-3 form-check">
                        <input id="anonymous" type="checkbox" name="anonymous" value="1"
                               class="form-check-input" {{ old('anonymous') ? 'checked' : '' }}>
                        <label for="anonymous" class="form-check-label">{{ __('torrents.anonymous_upload') }}</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('torrents.index') }}" class="btn btn-outline-secondary">{{ __('torrents.torrents') }}</a>
                        <button type="submit" class="btn btn-success">{{ __('torrents.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('uploadForm').addEventListener('submit', function (e) {
    const file = document.getElementById('torrent').value;
    if (!file.toLowerCase().endsWith('.torrent')) {
        e.preventDefault();
        alert('Please select a .torrent file.');
    }
});
</script>
@endpush
@endsection
