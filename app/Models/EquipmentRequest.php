<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentRequest extends Model
{
    protected $fillable = [
        'equipment_id', 'user_id', 'quantity', 'purpose',
        'date_needed', 'expected_return_date', 'actual_return_date',
        'status', 'remarks', 'approved_by',
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
        return $this->return_photo ? asset('storage/' . $this->return_photo) : null;
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
}
