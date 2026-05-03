@php
    $activePoll = \App\Models\Poll::where('active', true)->with('options.votes')->first();
    $hasVoted   = $activePoll && auth()->check() && $activePoll->userHasVoted(auth()->id());
    $total      = $activePoll ? $activePoll->totalVotes() : 0;
@endphp

@if($activePoll)
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-bar-chart-fill"></i> {{ __('polls.poll') }}</div>
    <div class="card-body py-2 px-3">
        <p class="fw-semibold small mb-2">{{ $activePoll->title }}</p>

        @if($hasVoted || !auth()->check())
            {{-- Results view --}}
            @foreach($activePoll->options as $option)
                @php
                    $count   = $option->votes->count();
                    $percent = $total > 0 ? round($count / $total * 100) : 0;
                @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span>{{ $option->text }}</span>
                        <span class="text-muted">{{ $percent }}%</span>
                    </div>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar" style="width:{{ $percent }}%"></div>
                    </div>
                </div>
            @endforeach
            <div class="text-muted small mt-2">{{ $total }} {{ __('polls.votes') }}</div>
            @if(!auth()->check())
                <div class="small mt-1">
                    <a href="{{ route('login') }}">{{ __('auth.login') }}</a> {{ __('polls.login_to_vote') }}
                </div>
            @endif
        @else
            {{-- Vote form --}}
            <form method="POST" action="{{ route('polls.vote', $activePoll) }}">
                @csrf
                @foreach($activePoll->options as $option)
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="radio"
                               name="option_id" value="{{ $option->id }}"
                               id="opt{{ $option->id }}" required>
                        <label class="form-check-label small" for="opt{{ $option->id }}">
                            {{ $option->text }}
                        </label>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-sm btn-primary mt-2 w-100">
                    {{ __('polls.vote') }}
                </button>
            </form>
        @endif
    </div>
</div>
@endif
