<x-guest-layout>
    <h5 class="card-title mb-3">Confirm Password</h5>
    <p class="text-muted small mb-4">This is a secure area. Please confirm your password to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">Confirm</button>
    </form>
</x-guest-layout>
