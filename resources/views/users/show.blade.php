@extends('layouts.app')

@section('title', $profile->username)

@section('content')
<div class="row g-4">
    {{-- Profile card --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center py-4">
                @if($profile->avatar)
                    <img src="{{ $profile->avatar }}" alt="Avatar"
                         class="rounded-circle mb-3" style="width:80px;height:80px;object-fit:cover;">
                @else
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center
                                text-white fw-bold mx-auto mb-3"
                         style="width:80px;height:80px;font-size:2rem;">
                        {{ strtoupper(substr($profile->username, 0, 1)) }}
                    </div>
                @endif
                <h5 class="mb-1">{{ $profile->username }}</h5>
                <span class="badge bg-primary mb-3">{{ $profile->level?->level_name ?? 'Member' }}</span>

                @auth
                    @if(auth()->id() === $profile->id || auth()->user()->isAdmin())
                        <div class="d-grid">
                            <a href="{{ route('account.edit') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-gear"></i> Edit Settings
                            </a>
                        </div>
                    @endif
                @endauth
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between small">
                    <span class="text-muted">{{ __('users.joined') }}</span>
                    <span>{{ $profile->created_at?->format('d M Y') ?? '—' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between small">
                    <span class="text-muted">{{ __('users.uploaded') }}</span>
                    <span>{{ $profile->uploaded ? number_format($profile->uploaded / 1073741824, 2) . ' GB' : '0 GB' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between small">
                    <span class="text-muted">{{ __('users.downloaded') }}</span>
                    <span>{{ $profile->downloaded ? number_format($profile->downloaded / 1073741824, 2) . ' GB' : '0 GB' }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between small">
                    <span class="text-muted">{{ __('users.ratio') }}</span>
                    <span>
                        @if($profile->downloaded > 0)
                            {{ number_format($profile->uploaded / $profile->downloaded, 2) }}
                        @else
                            &infin;
                        @endif
                    </span>
                </li>
                @auth
                    @if(auth()->user()->isAdmin())
                        <li class="list-group-item d-flex justify-content-between small">
                            <span class="text-muted">{{ __('users.last_ip') }}</span>
                            <code>{{ $profile->cip ?? '—' }}</code>
                        </li>
                        <li class="list-group-item d-flex justify-content-between small">
                            <span class="text-muted">{{ __('users.email') }}</span>
                            <span>{{ $profile->email }}</span>
                        </li>
                    @endif
                @endauth
            </ul>
        </div>
    </div>

    {{-- Recent snatches --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">{{ __('users.recent_downloads') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('torrents.torrents') }}</th>
                            <th class="text-center" style="width:80px">{{ __('users.uploaded') }}</th>
                            <th class="text-center" style="width:80px">{{ __('users.downloaded') }}</th>
                            <th class="text-center" style="width:100px">{{ __('torrents.added') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snatches as $snatch)
                            <tr>
                                <td class="small">
                                    @if($snatch->torrent)
                                        <a href="{{ route('torrents.show', $snatch->torrent->info_hash) }}"
                                           class="text-decoration-none">
                                            {{ Str::limit($snatch->torrent->filename, 60) }}
                                        </a>
                                    @else
                                        <em class="text-muted">Deleted torrent</em>
                                    @endif
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $snatch->uploaded ? number_format($snatch->uploaded / 1048576, 1) . ' MB' : '—' }}
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $snatch->downloaded ? number_format($snatch->downloaded / 1048576, 1) . ' MB' : '—' }}
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $snatch->date ? \Carbon\Carbon::parse($snatch->date)->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">{{ __('users.no_downloads') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
