<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'objectives', 'event_type', 'event_date',
        'time_start', 'time_end', 'venue', 'max_participants',
        'image_banner', 'organizer_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function getRegCountAttribute(): int
    {
        return $this->registrations()->count();
    }

    public function isFull(): bool
    {
        return $this->max_participants > 0 && $this->reg_count >= $this->max_participants;
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['upcoming', 'ongoing']);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['upcoming', 'ongoing']);
    }
}
