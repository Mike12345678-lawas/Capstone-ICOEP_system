@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Add Equipment</h1>
        <p class="page-subtitle">Add a new item to the equipment inventory.</p>
    </div>
    <a href="{{ url('/dashboard?section=inventory') }}" class="btn btn-secondary">← Back to Inventory</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="POST" action="{{ url('/equipment') }}" class="form-grid">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Equipment Name *</label>
                <input type="text" id="name" name="name" class="form-control @error('name') error @enderror"
                       value="{{ old('name') }}" placeholder="e.g., LCD Projector" required>
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category *</label>
                <input type="text" id="category" name="category" class="form-control @error('category') error @enderror"
                       value="{{ old('category') }}" placeholder="e.g., Audio/Visual" required
                       list="category-list">
                <datalist id="category-list">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">
                    @endforeach
                </datalist>
                @error('category') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"
                          placeholder="Brief description of the equipment...">{{ old('description') }}</textarea>
                @error('description') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="quantity">Total Quantity *</label>
                <input type="number" id="quantity" name="quantity" class="form-control @error('quantity') error @enderror"
                       value="{{ old('quantity', 1) }}" min="1" required>
                @error('quantity') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Status *</label>
                <select id="status" name="status" class="form-select @error('status') error @enderror" required>
                    <option value="available" {{ old('status') === 'available' ? 'selected' : '' }}>Available</option>
                    <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="retired" {{ old('status') === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
                @error('status') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            @if(isset($isUnitScoped) && $isUnitScoped)
                <div class="form-group">
                    <label class="form-label" for="custodian_unit_name">Custodian Unit</label>
                    <input type="text" id="custodian_unit_name" class="form-control" value="{{ $userUnit?->name }}" disabled>
                    <span class="form-hint">Automatically assigned to your unit.</span>
                </div>
            @else
                <div class="form-group">
                    <label class="form-label" for="custodian_unit_id">Custodian Unit *</label>
                    <select id="custodian_unit_id" name="custodian_unit_id" class="form-select @error('custodian_unit_id') error @enderror" required>
                        <option value="">— Select Custodian Unit —</option>
                        @foreach($custodianUnits as $u)
                            <option value="{{ $u->id }}" {{ old('custodian_unit_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    @error('custodian_unit_id') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="form-actions" style="grid-column: 1 / -1;">
                <button type="submit" class="btn btn-primary">Create Equipment</button>
                <a href="{{ url('/dashboard?section=inventory') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
