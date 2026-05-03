@extends('layouts.app')

@section('title', __('users.settings'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <h4 class="mb-4">{{ __('users.settings') }}</h4>

        {{-- Profile --}}
        <div class="card mb-4">
            <div class="card-header">{{ __('users.profile') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">{{ __('users.username') }}</label>
                        <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('users.email') }}</label>
                        <input id="email" type="email" name="email" maxlength="100"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="avatar" class="form-label">{{ __('users.avatar_url') }}</label>
                        <input id="avatar" type="url" name="avatar" maxlength="200"
                               class="form-control @error('avatar') is-invalid @enderror"
                               value="{{ old('avatar', $user->avatar) }}" placeholder="https://…">
                        @error('avatar')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('users.save') }}</button>
                </form>
            </div>
        </div>

        {{-- Password --}}
        <div class="card mb-4">
            <div class="card-header">{{ __('users.change_password') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">{{ __('users.current_password') }}</label>
                        <input id="current_password" type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">{{ __('users.new_password') }}</label>
                        <input id="new_password" type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">{{ __('users.confirm_password') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-warning">{{ __('users.update_password') }}</button>
                </form>
            </div>
        </div>

        {{-- Passkey --}}
        <div class="card">
            <div class="card-header">{{ __('users.passkey') }}</div>
            <div class="card-body">
                <p class="text-muted small mb-2">{{ __('users.passkey_hint') }}</p>
                <p class="mb-3">
                    <code class="user-select-all">{{ $user->passkey }}</code>
                </p>
                <form method="POST" action="{{ route('passkey.regenerate') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Regenerate passkey? You must re-download all torrents to keep seeding.')">
                        {{ __('users.regenerate_passkey') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
