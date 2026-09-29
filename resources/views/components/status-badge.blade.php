@props(['status'])

@php
$s = strtolower(trim((string) $status));
$badgeClass = match($s) {
    'approved', 'returned', 'active', 'available', 'ongoing', 'stock ok', 'no conflict' => 'badge-success',
    'rejected', 'cancelled', 'locked', 'retired', 'unavailable' => 'badge-error',
    'borrowed', 'upcoming' => 'badge-general',
    'pending', 'maintenance', 'completed' => 'badge-warning',
    default => 'badge-warning',
};
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $badgeClass]) }}>{{ ucfirst($status) }}</span>
