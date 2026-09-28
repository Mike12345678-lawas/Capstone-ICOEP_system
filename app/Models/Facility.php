<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    protected $fillable = ['name', 'location', 'description', 'capacity', 'image', 'status'];

    public function reservations(): HasMany
    {
        return $this->hasMany(FacilityReservation::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }
}
