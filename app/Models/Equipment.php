<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    protected $table = 'equipment';

    protected $fillable = ['name', 'category', 'description', 'quantity', 'available_quantity', 'image', 'status'];

    public function requests(): HasMany
    {
        return $this->hasMany(EquipmentRequest::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')->where('available_quantity', '>', 0);
    }

    /**
     * Compute available equipment quantity for a specific date range
     * based on overlapping approved and borrowed requests.
     */
    public function availableStockForRange($startDate, $endDate, $excludeRequestId = null): int
    {
        $query = $this->requests()
            ->whereIn('status', ['approved', 'borrowed'])
            ->where('date_needed', '<=', $endDate)
            ->where('expected_return_date', '>=', $startDate);

        if ($excludeRequestId) {
            $query->where('id', '!=', $excludeRequestId);
        }

        $overlappingDemand = (int) $query->sum('quantity');

        return max(0, $this->quantity - $overlappingDemand);
    }
}
