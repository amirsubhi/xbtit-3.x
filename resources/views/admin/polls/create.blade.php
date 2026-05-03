@extends('layouts.app')

@section('title', 'New Poll')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">New Poll</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.polls.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Question <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required maxlength="255">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="active" value="1" id="active"
                               class="form-check-input" {{ old('active') ? 'checked' : '' }}>
                        <label for="active" class="form-check-label">
                            Make active immediately (deactivates current poll)
                        </label>
                    </div>

                    <label class="form-label">Options <span class="text-danger">*</span></label>
                    <div id="options-list">
                        @for($i = 0; $i < 4; $i++)
                            <div class="input-group mb-2">
                                <span class="input-group-text">{{ $i + 1 }}</span>
                                <input type="text" name="options[]"
                                       class="form-control @error('options.' . $i) is-invalid @enderror"
                                       value="{{ old('options.' . $i) }}"
                                       maxlength="255"
                                       {{ $i < 2 ? 'required' : '' }}
                                       placeholder="{{ $i < 2 ? 'Required' : 'Optional' }}">
                            </div>
                        @endfor
                    </div>
                    @error('options')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="{{ route('admin.polls.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Poll</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
