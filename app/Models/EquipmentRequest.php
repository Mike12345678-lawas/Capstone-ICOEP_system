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
    ];

    protected function casts(): array
    {
        return [
            'date_needed' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
