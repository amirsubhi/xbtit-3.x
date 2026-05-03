<x-guest-layout>
    <h5 class="card-title mb-3">Verify Your Email</h5>
    <p class="text-muted small mb-3">
        Thanks for signing up! Please verify your email address by clicking the link we sent you.
        If you didn't receive it, we can send another.
    </p>

    @if(session('status') === 'verification-link-sent')
        <div class="alert alert-success mb-3">A new verification link has been sent to your email.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-primary w-100">Resend Verification Email</button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link p-0 text-muted small">Log Out</button>
    </form>
</x-guest-layout>
