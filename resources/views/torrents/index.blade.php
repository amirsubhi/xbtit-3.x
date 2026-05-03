@extends('layouts.app')

@section('title', __('torrents.torrents'))

@section('content')
<div class="row g-4">
<div class="col-lg-9">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">{{ __('torrents.torrents') }}</h4>
    @auth
        <a href="{{ route('torrents.create') }}" class="btn btn-sm btn-success">
            <i class="bi bi-cloud-upload"></i> {{ __('torrents.upload') }}
        </a>
    @endauth
</div>

{{-- Search / filter bar --}}
<form method="GET" action="{{ route('torrents.index') }}" class="card card-body mb-3 p-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small mb-1">{{ __('torrents.search') }}</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   placeholder="{{ __('torrents.search_placeholder') }}" value="{{ request('search') }}">
        </div>
        <div class="col-md-4">
            <label class="form-label small mb-1">{{ __('torrents.category') }}</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">{{ __('torrents.all_categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ request('category') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @foreach($cat->children as $sub)
                        <option value="{{ $sub->id }}"
                            {{ request('category') == $sub->id ? 'selected' : '' }}>
                            &nbsp;&nbsp;{{ $sub->name }}
                        </option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">{{ __('torrents.status') }}</label>
            <select name="active" class="form-select form-select-sm">
                <option value="0" {{ request('active', 1) == 0 ? 'selected' : '' }}>{{ __('torrents.status_all') }}</option>
                <option value="1" {{ request('active', 1) == 1 ? 'selected' : '' }}>{{ __('torrents.status_active') }}</option>
                <option value="2" {{ request('active', 1) == 2 ? 'selected' : '' }}>{{ __('torrents.status_dead') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('torrents.search') }}</button>
        </div>
    </div>
</form>

{{-- Results --}}
<div class="table-responsive">
    <table class="table table-sm table-hover table-striped align-middle">
        <thead class="table-dark">
            <tr>
                <th style="width:130px">{{ __('torrents.category') }}</th>
                <th>{{ __('torrents.name') }}</th>
                <th class="text-center" style="width:80px">{{ __('torrents.size') }}</th>
                <th class="text-center" style="width:70px">
                    <a href="{{ request()->fullUrlWithQuery(['order' => 5, 'by' => request('by') === '0' ? '1' : '0']) }}"
                       class="text-white text-decoration-none">{{ __('torrents.seeds') }}</a>
                </th>
                <th class="text-center" style="width:70px">{{ __('torrents.leechers') }}</th>
                <th class="text-center" style="width:70px">{{ __('torrents.completed') }}</th>
                <th class="text-center" style="width:100px">
                    <a href="{{ request()->fullUrlWithQuery(['order' => 3, 'by' => request('by') === '0' ? '1' : '0']) }}"
                       class="text-white text-decoration-none">{{ __('torrents.added') }}</a>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($torrents as $torrent)
                <tr>
                    <td>
                        <span class="badge bg-secondary">{{ $torrent->category?->name ?? '—' }}</span>
                    </td>
                    <td>
                        <a href="{{ route('torrents.show', $torrent->info_hash) }}"
                           class="text-decoration-none fw-semibold">
                            {{ $torrent->filename }}
                        </a>
                    </td>
                    <td class="text-center text-muted small">
                        {{ $torrent->size ? number_format($torrent->size / 1048576, 1) . ' MB' : '—' }}
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $torrent->seeds > 0 ? 'bg-success' : 'bg-secondary' }}">
                            {{ $torrent->seeds }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $torrent->leechers > 0 ? 'bg-warning text-dark' : 'bg-secondary' }}">
                            {{ $torrent->leechers }}
                        </span>
                    </td>
                    <td class="text-center text-muted small">{{ $torrent->finished ?? 0 }}</td>
                    <td class="text-center text-muted small">
                        {{ $torrent->created_at?->diffForHumans() ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('torrents.no_torrents') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center">
    {{ $torrents->appends(request()->query())->links('pagination::bootstrap-5') }}
</div>

</div>{{-- /col-lg-9 --}}

{{-- Sidebar --}}
<div class="col-lg-3">
    <x-poll />
    <x-shoutbox />
</div>

</div>{{-- /row --}}
@endsection
