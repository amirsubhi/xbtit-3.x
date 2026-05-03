@extends('layouts.app')

@section('title', __('admin.settings'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('admin.settings') }}</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PATCH')

    @foreach($grouped as $group => $settings)
        <div class="card mb-4">
            <div class="card-header fw-semibold text-capitalize">{{ $group }}</div>
            <div class="card-body">
                @foreach($settings as $setting)
                    <div class="mb-3">
                        @if($setting->type === 'bool')
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       id="s_{{ $setting->key }}" name="{{ $setting->key }}"
                                       value="true"
                                       {{ $setting->value === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="s_{{ $setting->key }}">
                                    {{ $setting->label ?: $setting->key }}
                                </label>
                            </div>
                        @elseif($setting->type === 'int')
                            <label class="form-label" for="s_{{ $setting->key }}">
                                {{ $setting->label ?: $setting->key }}
                            </label>
                            <input type="number" class="form-control @error($setting->key) is-invalid @enderror"
                                   id="s_{{ $setting->key }}" name="{{ $setting->key }}"
                                   value="{{ old($setting->key, $setting->value) }}">
                        @elseif($setting->type === 'json')
                            <label class="form-label" for="s_{{ $setting->key }}">
                                {{ $setting->label ?: $setting->key }}
                                <small class="text-muted">(JSON)</small>
                            </label>
                            <textarea class="form-control font-monospace @error($setting->key) is-invalid @enderror"
                                      id="s_{{ $setting->key }}" name="{{ $setting->key }}"
                                      rows="3">{{ old($setting->key, $setting->value) }}</textarea>
                        @elseif($setting->key === 'default_theme')
                            <label class="form-label" for="s_{{ $setting->key }}">
                                {{ $setting->label ?: 'Default Theme' }}
                            </label>
                            <select class="form-select" id="s_{{ $setting->key }}" name="{{ $setting->key }}">
                                @foreach(\App\Models\User::THEMES as $tk => $tl)
                                    <option value="{{ $tk }}" @selected(old($setting->key, $setting->value) === $tk)>{{ $tl }}</option>
                                @endforeach
                            </select>
                        @else
                            <label class="form-label" for="s_{{ $setting->key }}">
                                {{ $setting->label ?: $setting->key }}
                            </label>
                            <input type="text" class="form-control @error($setting->key) is-invalid @enderror"
                                   id="s_{{ $setting->key }}" name="{{ $setting->key }}"
                                   value="{{ old($setting->key, $setting->value) }}">
                        @endif
                        @error($setting->key)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy"></i> {{ __('admin.save_settings') }}
        </button>
    </div>
</form>
@endsection
