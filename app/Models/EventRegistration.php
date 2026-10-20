<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'user_id', 'qr_code', 'has_attended', 'checked_in_at', 'registered_at'];

    protected function casts(): array
    {
        return [
            'has_attended'  => 'boolean',
            'registered_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Generate a unique QR code token for this registration.
     * Format: EVT-{event_id}-USR-{user_id}-{random_hex}
     */
    public static function generateQrToken(int $eventId, int $userId): string
    {
        return 'EVT-' . $eventId . '-USR-' . $userId . '-' . strtoupper(Str::random(12));
    }
}
