@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h4 class="mb-3">Welcome, {{ auth()->user()->username }}</h4>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-primary">
                    {{ auth()->user()->uploaded ? number_format(auth()->user()->uploaded / 1073741824, 2) : '0.00' }} GB
                </div>
                <div class="text-muted">Uploaded</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-danger">
                    {{ auth()->user()->downloaded ? number_format(auth()->user()->downloaded / 1073741824, 2) : '0.00' }} GB
                </div>
                <div class="text-muted">Downloaded</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body py-4">
                @php
                    $dl = auth()->user()->downloaded;
                    $ratio = $dl > 0 ? auth()->user()->uploaded / $dl : null;
                @endphp
                <div class="fs-3 fw-bold {{ $ratio === null || $ratio >= 1 ? 'text-success' : 'text-warning' }}">
                    {{ $ratio !== null ? number_format($ratio, 2) : '∞' }}
                </div>
                <div class="text-muted">Ratio</div>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <a href="{{ route('torrents.index') }}" class="btn btn-outline-primary">
        <i class="bi bi-magnet"></i> Browse Torrents
    </a>
    <a href="{{ route('torrents.create') }}" class="btn btn-outline-success">
        <i class="bi bi-cloud-upload"></i> Upload
    </a>
    <a href="{{ route('users.show', auth()->id()) }}" class="btn btn-outline-secondary">
        <i class="bi bi-person"></i> My Profile
    </a>
</div>
@endsection
