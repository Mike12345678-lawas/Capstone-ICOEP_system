<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityReservation extends Model
{
    protected $fillable = [
        'facility_id', 'user_id', 'title', 'description',
        'reservation_date', 'time_start', 'time_end',
        'status', 'remarks', 'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Check for scheduling conflicts.
     */
    public function scopeConflicts($query, int $facilityId, string $date, string $start, string $end)
    {
        return $query->where('facility_id', $facilityId)
            ->where('reservation_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->where('time_start', '<', $end)
                  ->where('time_end', '>', $start);
            });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
