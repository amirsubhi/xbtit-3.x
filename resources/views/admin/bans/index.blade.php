@extends('layouts.app')

@section('title', 'IP Bans — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">IP Ban Management</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="row g-4">
    {{-- Add ban --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Add IP Ban</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.bans.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">IP Start <span class="text-danger">*</span></label>
                        <input type="text" name="ip_start" class="form-control @error('ip_start') is-invalid @enderror"
                               value="{{ old('ip_start') }}" placeholder="e.g. 192.168.1.0" required>
                        @error('ip_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">IP End <small class="text-muted">(range, optional)</small></label>
                        <input type="text" name="ip_end" class="form-control @error('ip_end') is-invalid @enderror"
                               value="{{ old('ip_end') }}" placeholder="e.g. 192.168.1.255">
                        @error('ip_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment</label>
                        <input type="text" name="comment" class="form-control" value="{{ old('comment') }}" maxlength="255">
                    </div>
                    <button type="submit" class="btn btn-danger w-100">Add Ban</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Ban list --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>IP Range</th>
                            <th>Comment</th>
                            <th>Added by</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bans as $ban)
                            <tr>
                                <td class="font-monospace small">
                                    {{ long2ip($ban->first) }}
                                    @if($ban->first !== $ban->last)
                                        — {{ long2ip($ban->last) }}
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $ban->comment ?: '—' }}</td>
                                <td class="text-muted small">{{ $ban->admin?->username ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.bans.destroy', $ban->id) }}"
                                          onsubmit="return confirm('Remove this ban?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">No IP bans.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $bans->links() }}</div>
    </div>
</div>
@endsection
