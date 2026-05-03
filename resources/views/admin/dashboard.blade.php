@extends('layouts.app')

@section('title', __('admin.dashboard'))

@section('content')
<h4 class="mb-4">{{ __('admin.dashboard') }}</h4>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ \App\Models\User::count() }}</div>
                        <div class="small">{{ __('admin.users') }}</div>
                    </div>
                    <i class="bi bi-people fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ \App\Models\Torrent::count() }}</div>
                        <div class="small">{{ __('admin.torrents') }}</div>
                    </div>
                    <i class="bi bi-magnet fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ \App\Models\Peer::count() }}</div>
                        <div class="small">{{ __('admin.active_peers') }}</div>
                    </div>
                    <i class="bi bi-broadcast fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-4 fw-bold">{{ \App\Models\News::count() }}</div>
                        <div class="small">{{ __('admin.news_articles') }}</div>
                    </div>
                    <i class="bi bi-newspaper fs-2 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">{{ __('admin.recent_users') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('users.username') }}</th>
                            <th>{{ __('admin.level') }}</th>
                            <th>{{ __('admin.joined') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\User::with('level')->latest()->limit(10)->get() as $user)
                            <tr>
                                <td>
                                    <a href="{{ route('users.show', $user->id) }}" class="text-decoration-none">
                                        {{ $user->username }}
                                    </a>
                                </td>
                                <td><span class="badge bg-secondary">{{ $user->level?->level_name ?? '—' }}</span></td>
                                <td class="small text-muted">{{ $user->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">{{ __('admin.recent_torrents') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('torrents.name') }}</th>
                            <th class="text-center">{{ __('admin.seeds') }}</th>
                            <th>{{ __('admin.joined') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\Torrent::latest()->limit(10)->get() as $torrent)
                            <tr>
                                <td class="small">
                                    <a href="{{ route('torrents.show', $torrent->info_hash) }}"
                                       class="text-decoration-none">
                                        {{ Str::limit($torrent->filename, 40) }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $torrent->seeds > 0 ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $torrent->seeds }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $torrent->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
