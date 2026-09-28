<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'content', 'priority', 'audience',
        'posted_by', 'image', 'is_pinned', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Scope to filter by audience visibility for a given role.
     */
    public function scopeVisibleTo($query, string $roleName)
    {
        return $query->where(function ($q) use ($roleName) {
            $q->where('audience', 'all')
              ->orWhere(function ($q2) use ($roleName) {
                  $q2->where('audience', 'students')->where(fn($q3) => $roleName === 'Student' ? $q3 : $q3->whereRaw('1=0'))
                     ;
              });
            // Simplified: admins/staff see all, others see matching audience
            if (in_array($roleName, ['Administrator', 'Staff'])) {
                // Already covered by 'all' + will see everything due to manage permission
            } else {
                $q->orWhere('audience', strtolower($roleName) . 's');
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('created_at');
    }
}
