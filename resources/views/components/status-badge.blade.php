@props(['status'])

@php
$s = strtolower(trim((string) $status));
$badgeClass = match($s) {
    'approved', 'returned', 'active', 'available', 'ongoing', 'stock ok', 'no conflict', 'open' => 'badge-success',
    'rejected', 'cancelled', 'locked', 'retired', 'unavailable', 'occupied' => 'badge-error',
    'borrowed', 'upcoming', 'completed' => 'badge-general',
    'pending', 'maintenance', 'expired' => 'badge-warning',
    default => 'badge-warning',
};
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $badgeClass]) }}>{{ ucfirst($status) }}</span>
