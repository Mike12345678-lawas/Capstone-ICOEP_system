<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
            ->whereDate('reservation_date', $date)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->where('time_start', '<', $end)
                  ->where('time_end', '>', $start);
            });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ─── Usage state (Occupied → Open is automatic once the end time passes) ───

    public function getStartsAtAttribute(): Carbon
    {
        return Carbon::parse($this->reservation_date->toDateString() . ' ' . $this->time_start);
    }

    public function getEndsAtAttribute(): Carbon
    {
        return Carbon::parse($this->reservation_date->toDateString() . ' ' . $this->time_end);
    }

    /**
     * For approved bookings: 'upcoming', 'occupied' (in use right now) or 'completed'
     * (time has ended, facility is open again). Other statuses return the raw status,
     * except pending requests whose time already passed, which are 'expired'.
     */
    public function getUsageStateAttribute(): string
    {
        $now = Carbon::now();

        if ($this->status === 'approved') {
            if ($now->gte($this->ends_at)) {
                return 'completed';
            }
            return $now->gte($this->starts_at) ? 'occupied' : 'upcoming';
        }

        if ($this->status === 'pending' && $now->gte($this->ends_at)) {
            return 'expired';
        }

        return $this->status;
    }

    /** Bookings that have NOT ended yet (end date/time is in the future). */
    public function scopeNotEnded($query)
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->whereDate('reservation_date', '>', $now->toDateString())
              ->orWhere(function ($q2) use ($now) {
                  $q2->whereDate('reservation_date', $now->toDateString())
                     ->where('time_end', '>', $now->format('H:i:s'));
              });
        });
    }

    /** Bookings whose end date/time has already passed. */
    public function scopeEnded($query)
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->whereDate('reservation_date', '<', $now->toDateString())
              ->orWhere(function ($q2) use ($now) {
                  $q2->whereDate('reservation_date', $now->toDateString())
                     ->where('time_end', '<=', $now->format('H:i:s'));
              });
        });
    }

    /** Approved bookings that are happening right now (facility is occupied). */
    public function scopeOngoing($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'approved')
            ->whereDate('reservation_date', $now->toDateString())
            ->where('time_start', '<=', $now->format('H:i:s'))
            ->where('time_end', '>', $now->format('H:i:s'));
    }
}
