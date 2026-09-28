@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Edit: {{ $equipment->name }}</h1>
        <p class="page-subtitle">Update equipment details and inventory status.</p>
    </div>
    <a href="{{ url('/dashboard?section=inventory') }}" class="btn btn-secondary">← Back to Inventory</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="POST" action="{{ url('/equipment/' . $equipment->id) }}" class="form-grid">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="name">Equipment Name *</label>
                <input type="text" id="name" name="name" class="form-control @error('name') error @enderror"
                       value="{{ old('name', $equipment->name) }}" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <input type="text" id="category" name="category" class="form-control @error('category') error @enderror"
                       value="{{ old('category', $equipment->category) }}" required list="category-list">
                <datalist id="category-list">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>
                @error('category') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $equipment->description) }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="quantity">Total Quantity *</label>
                <input type="number" id="quantity" name="quantity" class="form-control @error('quantity') error @enderror"
                       value="{{ old('quantity', $equipment->quantity) }}" min="1" required>
                @error('quantity') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="available_quantity">Available Quantity *</label>
                <input type="number" id="available_quantity" name="available_quantity" class="form-control @error('available_quantity') error @enderror"
                       value="{{ old('available_quantity', $equipment->available_quantity) }}" min="0" required>
                <span class="form-hint">Cannot exceed total quantity.</span>
                @error('available_quantity') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select @error('status') error @enderror" required>
                    <option value="available" {{ old('status', $equipment->status) === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="maintenance" {{ old('status', $equipment->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="retired" {{ old('status', $equipment->status) === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
                @error('status') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-actions" style="grid-column: 1 / -1;">
                <button type="submit" class="btn btn-primary">Update Equipment</button>
                <a href="{{ url('/dashboard?section=inventory') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
