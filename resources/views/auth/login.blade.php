<x-guest-layout>
    @if(session('status'))
        <div class="alert alert-success mb-3">{{ session('status') }}</div>
    @endif

    <h5 class="card-title mb-4">Log In</h5>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input id="username" type="text" name="username"
                   class="form-control @error('username') is-invalid @enderror"
                   value="{{ old('username') }}" required autofocus autocomplete="username">
            @error('username')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
            <label for="remember_me" class="form-check-label">Remember me</label>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small text-muted">Forgot password?</a>
            @else
                <span></span>
            @endif
            <button type="submit" class="btn btn-primary">Log In</button>
        </div>

        @if(Route::has('register'))
            <hr>
            <p class="text-center mb-0 small">No account? <a href="{{ route('register') }}">Register</a></p>
        @endif
    </form>
</x-guest-layout>
