@extends('layouts.app')

@section('title', 'Users — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">User Management</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" placeholder="Search username or email…"
               value="{{ request('search') }}">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="{{ $user->isLocked() ? 'table-danger' : '' }}">
                        <td class="text-muted small">{{ $user->id }}</td>
                        <td><strong>{{ $user->username }}</strong></td>
                        <td class="text-muted small">{{ $user->email }}</td>
                        <td>{{ $user->level?->level_name ?? '—' }}</td>
                        <td class="text-muted small">{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td>
                            @if($user->isLocked())
                                <span class="badge bg-danger">Locked</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.users.show', $user->id) }}"
                               class="btn btn-sm btn-outline-primary">Manage</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $users->withQueryString()->links() }}</div>
@endsection
