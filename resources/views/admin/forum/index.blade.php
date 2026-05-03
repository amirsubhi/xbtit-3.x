@extends('layouts.app')

@section('title', 'Forum Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Forum Management</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

{{-- Add Category --}}
<div class="card mb-4">
    <div class="card-header">Add Category</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.forum.category.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control" required maxlength="100">
            </div>
            <div class="col-md-2">
                <label class="form-label">Order</label>
                <input type="number" name="display_order" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Add Category</button>
            </div>
        </form>
    </div>
</div>

{{-- Categories + Forums --}}
@forelse($categories as $category)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">{{ $category->name }} <span class="text-muted small">(order: {{ $category->display_order }})</span></span>
            <form method="POST" action="{{ route('admin.forum.category.destroy', $category) }}">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Delete category and all its forums?')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
        <div class="card-body">
            {{-- Existing forums --}}
            @if($category->forums->isNotEmpty())
                <table class="table table-sm mb-3">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Description</th><th>Order</th><th>Threads</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($category->forums as $forum)
                            <tr>
                                <td>{{ $forum->name }}</td>
                                <td class="text-muted small">{{ $forum->description ?: '—' }}</td>
                                <td>{{ $forum->display_order }}</td>
                                <td>{{ $forum->thread_count }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.forum.forum.destroy', $forum) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Delete forum and all its threads?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Add forum to this category --}}
            <form method="POST"
                  action="{{ route('admin.forum.forum.store', $category) }}"
                  class="row g-2 align-items-end border-top pt-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small">Forum Name</label>
                    <input type="text" name="name" class="form-control form-control-sm" required maxlength="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Description</label>
                    <input type="text" name="description" class="form-control form-control-sm" maxlength="300">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Order</label>
                    <input type="number" name="display_order" class="form-control form-control-sm" value="0" min="0">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-sm btn-success w-100">Add Forum</button>
                </div>
            </form>
        </div>
    </div>
@empty
    <div class="alert alert-info">No categories yet. Add one above.</div>
@endforelse
@endsection
