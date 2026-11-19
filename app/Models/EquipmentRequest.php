<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EquipmentRequest extends Model
{
    protected $fillable = [
        'equipment_id', 'user_id', 'quantity', 'purpose',
        'date_needed', 'time_needed', 'expected_return_date', 'expected_return_time', 'actual_return_date',
        'status', 'remarks', 'approved_by', 'borrowed_at',
        'return_requested_at', 'return_photo', 'returned_quantity', 'returned_at',
        'confirmed_by', 'return_condition', 'return_notes', 'days_late',
    ];

    /** Statuses that are still "in progress" for the borrower. */
    public const ACTIVE_STATUSES = ['pending', 'approved', 'borrowed'];

    protected function casts(): array
    {
        return [
            'date_needed'          => 'date',
            'expected_return_date' => 'date',
            'actual_return_date'   => 'date',
            'return_requested_at'  => 'datetime',
            'returned_at'          => 'datetime',
            'borrowed_at'          => 'datetime',
            'returned_quantity'    => 'integer',
            'days_late'            => 'integer',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by')->withTrashed();
    }

    /** Public URL of the photo the borrower uploaded when returning the item. */
    public function getReturnPhotoUrlAttribute(): ?string
    {
        if (!$this->return_photo) {
            return null;
        }

        if (!Storage::disk('public')->exists($this->return_photo)
            && !file_exists(public_path('storage/' . $this->return_photo))
            && !file_exists(storage_path('app/public/' . $this->return_photo))) {
            return null;
        }

        return asset('storage/' . $this->return_photo);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedsCheck($query)
    {
        return $query->whereIn('status', ['borrowed', 'approved'])->whereNotNull('return_requested_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['borrowed', 'approved'])->where('expected_return_date', '<', now()->startOfDay());
    }

    public function scopeReturningToday($query)
    {
        return $query->whereIn('status', ['borrowed', 'approved'])->whereDate('expected_return_date', now()->toDateString());
    }

    public function getNeededAtAttribute(): ?Carbon
    {
        if (!$this->date_needed) return null;
        $time = $this->time_needed ? substr($this->time_needed, 0, 5) : '00:00';
        return Carbon::parse($this->date_needed->toDateString() . ' ' . $time);
    }

    public function getExpectedReturnAtAttribute(): ?Carbon
    {
        if (!$this->expected_return_date) return null;
        $time = $this->expected_return_time ? substr($this->expected_return_time, 0, 5) : '23:59';
        return Carbon::parse($this->expected_return_date->toDateString() . ' ' . $time);
    }
}
