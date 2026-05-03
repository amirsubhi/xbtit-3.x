@extends('layouts.app')

@section('title', 'Manage ' . $user->username . ' — Admin')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
        <li class="breadcrumb-item active">{{ $user->username }}</li>
    </ol>
</nav>

<div class="row g-4">
    {{-- User info --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">User Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5 text-muted">ID</dt>
                    <dd class="col-7">{{ $user->id }}</dd>
                    <dt class="col-5 text-muted">Username</dt>
                    <dd class="col-7">{{ $user->username }}</dd>
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7 small">{{ $user->email }}</dd>
                    <dt class="col-5 text-muted">Joined</dt>
                    <dd class="col-7">{{ $user->created_at?->format('Y-m-d') }}</dd>
                    <dt class="col-5 text-muted">Passkey</dt>
                    <dd class="col-7"><code class="small">{{ $user->passkey ?: '—' }}</code></dd>
                </dl>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Reset Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.reset-password', $user->id) }}">
                    @csrf
                    <p class="small text-muted mb-2">Sends a password reset link to {{ $user->email }}.</p>
                    <button type="submit" class="btn btn-warning w-100">Send Reset Email</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit form --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Edit User</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
                    @csrf @method('PATCH')

                    <div class="mb-3">
                        <label class="form-label">Account Level</label>
                        <select name="id_level" class="form-select @error('id_level') is-invalid @enderror" required>
                            @foreach($levels as $level)
                                <option value="{{ $level->id_level }}"
                                    @selected(old('id_level', $user->id_level) === $level->id_level)>
                                    {{ $level->level_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input type="hidden" name="locked" value="0">
                            <input class="form-check-input" type="checkbox" name="locked" id="locked" value="1"
                                   @checked(old('locked', $user->isLocked() ? 1 : 0))>
                            <label class="form-check-label text-danger" for="locked">Lock this account</label>
                        </div>
                        <small class="text-muted">Locked users cannot log in.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
