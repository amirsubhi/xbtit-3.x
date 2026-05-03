@extends('layouts.app')

@section('title', 'Categories — Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Torrent Categories</h4>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
</div>

<div class="row g-4">
    {{-- Add category --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Add Category</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required maxlength="100">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent (leave blank for top-level)</label>
                        <select name="sub" class="form-select">
                            <option value="">— Top level —</option>
                            @foreach($categories as $parent)
                                <option value="{{ $parent->id }}" @selected(old('sub') == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort index</label>
                        <input type="number" name="sort_index" class="form-control" value="{{ old('sort_index', 0) }}" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Category list --}}
    <div class="col-lg-8">
        @forelse($categories as $parent)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>{{ $parent->name }}</strong>
                    <form method="POST" action="{{ route('admin.categories.destroy', $parent->id) }}"
                          onsubmit="return confirm('Delete {{ addslashes($parent->name) }} and all its subcategories?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </div>
                @if($parent->children->isNotEmpty())
                    <ul class="list-group list-group-flush">
                        @foreach($parent->children as $child)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="ms-3 text-muted">↳ {{ $child->name }}</span>
                                <form method="POST" action="{{ route('admin.categories.destroy', $child->id) }}"
                                      onsubmit="return confirm('Delete {{ addslashes($child->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-muted">No categories yet.</p>
        @endforelse
    </div>
</div>
@endsection
