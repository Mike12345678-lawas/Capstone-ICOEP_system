@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $equipment->name }}</h1>
        <p class="page-subtitle">{{ $equipment->category }} &bull; {{ $equipment->available_quantity }}/{{ $equipment->quantity }} available</p>
    </div>
    <div class="header-actions-mobile">
        <a href="{{ url('/equipment') }}" class="btn btn-secondary">&larr; Back to List</a>
    </div>
</div>
<div class="grid-sidebar-layout">
    <div>
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header"><h3 class="card-title">Item Details</h3></div>
            <div class="card-body">
                <p style="line-height: 1.8;">{{ $equipment->description ?: 'No description available.' }}</p>
                <div style="margin-top: 1rem;">
                    <span class="badge badge-general">{{ $equipment->category }}</span>
                    <x-status-badge :status="$equipment->status" />
                </div>
            </div>
        </div>

        {{-- User's Own Borrow Requests for this Item --}}
        @if(isset($myRequests) && $myRequests->isNotEmpty())
        <div class="card animate-fade-in-up" style="animation-delay: 150ms; margin-top: 1.5rem; border-left: 4px solid var(--color-brand-500, #0ea5e9);">
            <div class="card-header">
                <h3 class="card-title">My Borrow Requests for this Item</h3>
                <a href="{{ url('/dashboard?section=borrowed-equipment') }}" class="btn btn-secondary btn-sm" style="font-size: .75rem;">View All Borrowed</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table responsive-card-table">
                    <thead>
                        <tr><th>Qty</th><th>Purpose</th><th>Schedule</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($myRequests as $mReq)
                        <tr>
                            <td data-label="Qty" style="font-weight: 600;">{{ $mReq->quantity }}</td>
                            <td data-label="Purpose">{{ $mReq->purpose }}</td>
                            <td data-label="Schedule" style="font-size: .85rem;">
                                <span>{{ $mReq->date_needed?->format('M d, Y') ?? '—' }}</span>
                                <div class="text-muted" style="font-size: .75rem;">Due {{ $mReq->expected_return_date?->format('M d, Y') ?? '—' }}</div>
                            </td>
                            <td data-label="Status">
                                <x-status-badge :status="$mReq->status" />
                                @if(in_array($mReq->status, ['borrowed', 'approved']) && $mReq->return_requested_at)
                                    <div><span class="badge badge-warning" style="font-size:.65rem; margin-top:.2rem;">Waiting for check</span></div>
                                @endif
                            </td>
                            <td data-label="Action" class="card-actions-cell">
                                @if($mReq->status === 'pending')
                                    <form method="POST" action="{{ url('/equipment-requests/' . $mReq->id . '/cancel') }}" style="margin:0;">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-secondary btn-sm" style="font-size:.75rem; color:var(--color-error);"
                                            onclick="return confirm('Cancel this request?')">Cancel</button>
                                    </form>
                                @elseif(in_array($mReq->status, ['borrowed', 'approved']))
                                    @if(is_null($mReq->return_requested_at))
                                        <button type="button" class="btn btn-primary btn-sm" style="font-size:.75rem;"
                                            id="return-btn-{{ $mReq->id }}"
                                            onclick="openReturnModal({{ $mReq->id }}, @js($mReq->equipment->name), {{ $mReq->quantity - $mReq->returned_quantity }})">
                                            Return Equipment
                                        </button>
                                    @else
                                        <form method="POST" action="{{ url('/equipment-requests/' . $mReq->id . '/cancel-return-request') }}" style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.75rem;">Cancel Return</button>
                                        </form>
                                    @endif
                                @elseif($mReq->status === 'returned')
                                    <span class="badge badge-success">Returned</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    <div class="card animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card-header"><h3 class="card-title">Borrow This Equipment</h3></div>
        <div class="card-body">
            @if($equipment->available_quantity < 1 || $equipment->status !== 'available')
                <div class="empty-state" style="padding: 1.25rem 0.5rem;">
                    <p class="text-muted" style="margin-bottom: 0.75rem;">No units currently available for immediate borrowing.</p>
                    @if(!empty($alternatives))
                        <div style="text-align: left; background: var(--color-surface-50); border: 1px solid var(--color-surface-200); border-radius: 12px; padding: 1rem; margin-top: 0.75rem;">
                            <div style="font-size: 0.8125rem; font-weight: 700; color: var(--color-surface-800); margin-bottom: 0.625rem; display: flex; align-items: center; gap: 0.35rem;">
                                <span>💡</span> Same-Function Alternatives & Available Times:
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                @foreach($alternatives as $alt)
                                    @if(($alt['type'] ?? '') === 'alternate_equipment')
                                        <a href="{{ url('/equipment/' . $alt['equipment_id']) }}" class="btn btn-secondary btn-sm" style="justify-content: space-between; font-size: 0.8125rem; padding: 0.5rem 0.75rem;">
                                            <span>📦 <strong>{{ $alt['name'] }}</strong></span>
                                            <span class="badge badge-general" style="font-size: 0.7rem;">{{ $alt['available'] }} avail.</span>
                                        </a>
                                    @elseif(($alt['type'] ?? '') === 'alternate_borrow_date')
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: var(--bg-card); border: 1px solid var(--color-surface-200); border-radius: 8px; font-size: 0.8125rem;">
                                            <span>🕒 <strong>Available:</strong> {{ $alt['label'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ url('/equipment/' . $equipment->id . '/borrow') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" required min="1" max="{{ $equipment->available_quantity }}" value="{{ old('quantity', request('quantity', 1)) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="purpose">Purpose *</label>
                        <input type="text" id="purpose" name="purpose" class="form-control" required value="{{ old('purpose', request('purpose')) }}" placeholder="e.g., Class presentation">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date_needed">Date Needed *</label>
                        <input type="date" id="date_needed" name="date_needed" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('date_needed', request('date_needed', request('date'))) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expected_return_date">Expected Return *</label>
                        <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('expected_return_date', request('expected_return_date', request('return_date'))) }}">
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center;">Submit Request</button>
                </form>
            @endif
        </div>
    </div>
</div>
@include('dashboard.partials._return_modal')
@endsection

