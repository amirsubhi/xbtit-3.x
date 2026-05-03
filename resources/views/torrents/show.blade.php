@extends('layouts.app')

@section('title', $torrent->filename . ' — ' . __('torrents.torrents'))

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('torrents.index') }}">{{ __('torrents.torrents') }}</a></li>
        <li class="breadcrumb-item active">{{ Str::limit($torrent->filename, 60) }}</li>
    </ol>
</nav>

<div class="row g-4">
    {{-- Main info --}}
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $torrent->filename }}</h5>
                <div class="d-flex gap-2">
                    @auth
                        @can('update', $torrent)
                            <a href="{{ route('torrents.edit', $torrent->info_hash) }}"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i> {{ __('torrents.edit') }}
                            </a>
                        @endcan
                        @can('delete', $torrent)
                            <form method="POST" action="{{ route('torrents.destroy', $torrent->info_hash) }}"
                                  class="d-inline" onsubmit="return confirm('Delete this torrent permanently?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        @endcan
                        <a href="{{ route('torrents.download', $torrent->info_hash) }}"
                           class="btn btn-sm btn-success">
                            <i class="bi bi-download"></i> {{ __('torrents.download') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('auth.login_to_download') }}
                        </a>
                    @endauth
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width:140px">{{ __('torrents.info_hash') }}</th>
                            <td><code>{{ $torrent->info_hash }}</code></td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('torrents.category') }}</th>
                            <td>{{ $torrent->category?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('torrents.size') }}</th>
                            <td>{{ $torrent->size ? number_format($torrent->size / 1048576, 2) . ' MB' : '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('torrents.uploaded_by') }}</th>
                            <td>
                                @if($torrent->anonymous === 'true')
                                    <em class="text-muted">{{ __('torrents.anonymous') }}</em>
                                @elseif($torrent->uploader)
                                    <a href="{{ route('users.show', $torrent->uploader->id) }}">
                                        {{ $torrent->uploader->username }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">{{ __('torrents.added') }}</th>
                            <td>{{ $torrent->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Description --}}
        @if($torrent->info)
            <div class="card mb-4">
                <div class="card-header">{{ __('torrents.description') }}</div>
                <div class="card-body">
                    {!! nl2br(e($torrent->info)) !!}
                </div>
            </div>
        @endif

        {{-- Comments --}}
        <div class="card">
            <div class="card-header">{{ __('torrents.comments') }} ({{ $torrent->comments->count() }})</div>
            <div class="card-body p-0">
                @forelse($torrent->comments as $comment)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong>
                                <a href="{{ route('users.show', $comment->author->id) }}" class="text-decoration-none">
                                    {{ $comment->author->username }}
                                </a>
                            </strong>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">{{ $comment->created_at?->diffForHumans() }}</small>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <form method="POST" action="{{ route('comments.destroy', $comment->id) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0"
                                                    onclick="return confirm('Delete this comment?')">
                                                {{ __('torrents.delete_comment') }}
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        </div>
                        <div>{!! nl2br(e($comment->body)) !!}</div>
                    </div>
                @empty
                    <p class="text-muted text-center py-3 mb-0">{{ __('torrents.no_comments') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Comment form --}}
        <div class="card mt-3">
            <div class="card-header">{{ __('torrents.add_comment') }}</div>
            <div class="card-body">
                @auth
                    <form method="POST" action="{{ route('comments.store', $torrent->info_hash) }}">
                        @csrf
                        <div class="mb-3">
                            <textarea name="body" rows="3" class="form-control @error('body') is-invalid @enderror"
                                      placeholder="{{ __('torrents.comment_body') }}"
                                      maxlength="2000" required>{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('torrents.post_comment') }}</button>
                    </form>
                @else
                    <p class="text-muted mb-0">
                        <a href="{{ route('login') }}">{{ __('torrents.login_to_comment') }}</a>
                    </p>
                @endauth
            </div>
        </div>
    </div>

    {{-- Stats sidebar --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">{{ __('torrents.stats') }}</div>
            <div class="card-body">
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-success">{{ $torrent->seeds }}</div>
                        <div class="small text-muted">{{ __('torrents.seeds') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold text-warning">{{ $torrent->leechers }}</div>
                        <div class="small text-muted">{{ __('torrents.leechers') }}</div>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-bold">{{ $torrent->finished ?? 0 }}</div>
                        <div class="small text-muted">{{ __('torrents.completed') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Peer list --}}
        @if($peers->isNotEmpty())
            <div class="card">
                <div class="card-header">{{ __('torrents.peers') }} ({{ $peers->count() }})</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('torrents.peer') }}</th>
                                <th class="text-center">{{ __('torrents.up') }}</th>
                                <th class="text-center">{{ __('torrents.down') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($peers as $peer)
                                <tr>
                                    <td class="small">
                                        <span class="badge {{ $peer->status === 'seeder' ? 'bg-success' : 'bg-warning text-dark' }}">
                                            {{ $peer->status }}
                                        </span>
                                        <code class="ms-1 small">{{ $peer->ip }}</code>
                                    </td>
                                    <td class="text-center small text-muted">
                                        {{ $peer->uploaded ? number_format($peer->uploaded / 1048576, 1) . 'M' : '0' }}
                                    </td>
                                    <td class="text-center small text-muted">
                                        {{ $peer->downloaded ? number_format($peer->downloaded / 1048576, 1) . 'M' : '0' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
