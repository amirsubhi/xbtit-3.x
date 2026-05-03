@extends('layouts.app')

@section('title', 'Torrents — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Torrent Management</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search by filename…"
               value="{{ request('search') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.torrents.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Uploader</th>
                    <th class="text-center">S/L</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($torrents as $torrent)
                    <tr>
                        <td>
                            <a href="{{ route('torrents.show', $torrent->info_hash) }}" class="text-decoration-none">
                                {{ Str::limit($torrent->filename, 60) }}
                            </a>
                        </td>
                        <td class="text-muted small">{{ $torrent->category?->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $torrent->uploader?->username ?? '—' }}</td>
                        <td class="text-center small">
                            <span class="text-success">{{ $torrent->seeds }}</span>/<span class="text-warning">{{ $torrent->leechers }}</span>
                        </td>
                        <td class="text-muted small">{{ $torrent->created_at?->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.torrents.edit', $torrent->info_hash) }}"
                               class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('admin.torrents.destroy', $torrent->info_hash) }}"
                                  class="d-inline" onsubmit="return confirm('Delete this torrent?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No torrents found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $torrents->withQueryString()->links() }}</div>
@endsection
