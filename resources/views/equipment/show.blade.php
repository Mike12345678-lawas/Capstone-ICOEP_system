@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $equipment->name }}</h1>
        <p class="page-subtitle">{{ $equipment->category }} &bull; {{ $equipment->available_quantity }}/{{ $equipment->quantity }} available</p>
    </div>
    <a href="{{ url('/equipment') }}" class="btn btn-secondary">&larr; Back to List</a>
</div>
<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <div>
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header"><h3 class="card-title">Item Details</h3></div>
            <div class="card-body">
                <p style="line-height: 1.8;">{{ $equipment->description ?: 'No description available.' }}</p>
                <div style="margin-top: 1rem;">
                    <span class="badge badge-general">{{ $equipment->category }}</span>
                    <span class="badge {{ $equipment->status === 'available' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($equipment->status) }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card-header"><h3 class="card-title">Borrow This Equipment</h3></div>
        <div class="card-body">
            @if($equipment->available_quantity < 1)
                <div class="empty-state" style="padding: 1.5rem;"><p class="text-muted">No units available for borrowing.</p></div>
            @else
                <form method="POST" action="{{ url('/equipment/' . $equipment->id . '/borrow') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" required min="1" max="{{ $equipment->available_quantity }}" value="{{ old('quantity', 1) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="purpose">Purpose *</label>
                        <input type="text" id="purpose" name="purpose" class="form-control" required value="{{ old('purpose') }}" placeholder="e.g., Class presentation">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date_needed">Date Needed *</label>
                        <input type="date" id="date_needed" name="date_needed" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('date_needed') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expected_return_date">Expected Return *</label>
                        <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('expected_return_date') }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Submit Request</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
