<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyConsent extends Model
{
    protected $fillable = ['user_id', 'consent_type', 'consented', 'ip_address'];

    protected function casts(): array
    {
        return [
            'consented' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
