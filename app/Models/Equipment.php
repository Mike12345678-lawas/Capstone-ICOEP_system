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
}
