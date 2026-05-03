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

        {{-- Language --}}
        <div class="card mb-4">
            <div class="card-header">{{ __('users.language') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('locale.switch') }}">
                    @csrf
                    <div class="row g-2">
                        @foreach(\App\Models\User::LOCALES as $code => $info)
                            <div class="col-6 col-md-4 col-lg-3">
                                <label class="d-block">
                                    <input type="radio" name="locale" value="{{ $code }}" class="d-none locale-radio"
                                           @checked(app()->getLocale() === $code)>
                                    <div class="locale-card d-flex align-items-center gap-2 p-2 rounded border"
                                         style="cursor:pointer; transition: border-color .15s;
                                                border-color: {{ app()->getLocale() === $code ? '#0d6efd' : 'var(--bs-border-color)' }} !important;">
                                        <span style="font-size:1.3rem">{{ $info['flag'] }}</span>
                                        <span class="small fw-semibold">{{ $info['name'] }}</span>
                                        @if($info['dir'] === 'rtl')
                                            <span class="badge bg-secondary ms-auto" style="font-size:.65rem">RTL</span>
                                        @endif
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">{{ __('users.save_language') }}</button>
                </form>
            </div>
        </div>

        {{-- Theme --}}
        <div class="card mb-4" id="theme">
            <div class="card-header">{{ __('users.theme') }}</div>
            <div class="card-body">
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf @method('PATCH')
                    <div class="row g-3">
                        @foreach(\App\Models\User::THEMES as $key => $label)
                            @php
                                [$themeName, $themeDesc] = explode(' (', rtrim($label, ')'), 2) + ['', ''];
                                $swatches = match($key) {
                                    'xbtit-default' => ['#2d4a6e','#edf0f3','#4c77b6','#006699'],
                                    'darklair'      => ['#030303','#1a1a1a','#e16503','#e16503'],
                                    'modern'        => ['#0a1628','#1e293b','#3b82f6','#60a5fa'],
                                    default         => ['#333','#fff','#007bff','#007bff'],
                                };
                            @endphp
                            <div class="col-md-4">
                                <label class="d-block cursor-pointer">
                                    <input type="radio" name="theme" value="{{ $key }}" class="d-none theme-radio"
                                           @checked(old('theme', $user->theme ?? 'xbtit-default') === $key)>
                                    <div class="theme-card card h-100 {{ (old('theme', $user->theme ?? 'xbtit-default') === $key) ? 'theme-selected' : '' }}"
                                         style="border: 2px solid {{ (old('theme', $user->theme ?? 'xbtit-default') === $key) ? '#0d6efd' : 'var(--bs-border-color)' }}; cursor: pointer; transition: border-color .2s">
                                        {{-- Mini preview --}}
                                        <div style="height:64px; background:{{ $swatches[0] }}; border-radius: .35rem .35rem 0 0; position:relative; overflow:hidden;">
                                            <div style="height:10px; background:{{ $swatches[0] }}; opacity:.7; position:absolute; top:0; left:0; right:0;"></div>
                                            <div style="position:absolute; bottom:8px; left:8px; right:8px; height:30px; background:{{ $swatches[1] }}; border-radius:3px; border:1px solid rgba(255,255,255,.1);">
                                                <div style="height:6px; background:{{ $swatches[2] }}; border-radius:3px 3px 0 0; opacity:.8;"></div>
                                            </div>
                                            <div style="position:absolute; top:8px; right:10px; width:18px; height:6px; background:{{ $swatches[3] }}; border-radius:2px; opacity:.9;"></div>
                                        </div>
                                        <div class="card-body p-2">
                                            <div class="fw-semibold small">{{ $themeName }}</div>
                                            @if($themeDesc)
                                                <div class="text-muted" style="font-size:.72rem">{{ $themeDesc }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">{{ __('users.save_theme') }}</button>
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
@push('scripts')
<script>
document.querySelectorAll('.theme-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.theme-card').forEach(c => {
            c.style.borderColor = 'var(--bs-border-color)';
        });
        radio.closest('label').querySelector('.theme-card').style.borderColor = '#0d6efd';
    });
});
document.querySelectorAll('.locale-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.locale-card').forEach(c => {
            c.style.borderColor = 'var(--bs-border-color)';
        });
        radio.closest('label').querySelector('.locale-card').style.borderColor = '#0d6efd';
    });
});
</script>
@endpush
@endsection
