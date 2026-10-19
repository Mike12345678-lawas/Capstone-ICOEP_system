<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_FACULTY = 'faculty';
    public const AUDIENCE_STAFF = 'staff';
    public const AUDIENCE_STUDENT = 'student';
    public const AUDIENCE_BSIT = 'bsit';
    public const AUDIENCE_BSHM = 'bshm';
    public const AUDIENCE_BSBA_FM = 'bsba-fm';
    public const AUDIENCE_BSED = 'bsed';
    public const AUDIENCE_BEED = 'beed';

    /**
     * Non-teaching staff roles.
     */
    public const STAFF_ROLES = [
        'Staff',
        'Registrar',
        'Librarian',
        'Maintenance Staff',
        'ComLab Staff',
    ];

    /**
     * Map of department-specific audiences to canonical Department code.
     */
    public const DEPARTMENT_AUDIENCES = [
        self::AUDIENCE_BSIT    => 'BSIT',
        self::AUDIENCE_BSHM    => 'BSHM',
        self::AUDIENCE_BSBA_FM => 'BSBA-FM',
        self::AUDIENCE_BSED    => 'BSEd',
        self::AUDIENCE_BEED    => 'BEEd',
    ];

    public const AUDIENCES = [
        self::AUDIENCE_ALL     => 'All Campus',
        self::AUDIENCE_FACULTY => 'Faculty Only',
        self::AUDIENCE_STAFF   => 'Staff (Non-Teaching) Only',
        self::AUDIENCE_STUDENT => 'Students Only',
        self::AUDIENCE_BSIT    => 'BSIT Only',
        self::AUDIENCE_BSHM    => 'BSHM Only',
        self::AUDIENCE_BSBA_FM => 'BSBA-FM Only',
        self::AUDIENCE_BSED    => 'BSED Only',
        self::AUDIENCE_BEED    => 'BEED Only',
    ];

    protected $fillable = [
        'title', 'description', 'objectives', 'event_type', 'target_audience', 'event_date',
        'time_start', 'time_end', 'venue', 'max_participants',
        'image_banner', 'organizer_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function setTargetAudienceAttribute($value): void
    {
        if (is_array($value)) {
            $filtered = array_values(array_filter($value));
            $this->attributes['target_audience'] = count($filtered) === 1 ? $filtered[0] : (empty($filtered) ? self::AUDIENCE_ALL : json_encode($filtered));
        } else {
            $this->attributes['target_audience'] = $value ?: self::AUDIENCE_ALL;
        }
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

    /**
     * Get array of all target audiences for this event.
     * Supports JSON arrays, comma-separated strings, or single string values.
     *
     * @return string[]
     */
    public function getTargetAudiences(): array
    {
        $val = $this->target_audience;

        if (is_array($val)) {
            return empty($val) ? [self::AUDIENCE_ALL] : array_values($val);
        }

        if (empty($val)) {
            return [self::AUDIENCE_ALL];
        }

        if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
            $decoded = json_decode($val, true);
            if (is_array($decoded) && !empty($decoded)) {
                return array_values($decoded);
            }
        }

        if (str_contains($val, ',')) {
            return array_filter(array_map('trim', explode(',', $val)));
        }

        return [$val];
    }

    public function getTargetAudienceLabelAttribute(): string
    {
        $audiences = $this->getTargetAudiences();
        if (in_array(self::AUDIENCE_ALL, $audiences, true)) {
            return 'All Campus';
        }

        $labels = [];
        foreach ($audiences as $aud) {
            $labels[] = self::AUDIENCES[$aud] ?? ucfirst($aud);
        }

        return !empty($labels) ? implode(', ', $labels) : 'All Campus';
    }

    public function isFull(): bool
    {
        return $this->max_participants > 0 && $this->reg_count >= $this->max_participants;
    }

    public function isUpcoming(): bool
    {
        return in_array($this->status, ['upcoming', 'ongoing']);
    }

    public function isVisibleTo(?User $user): bool
    {
        $audiences = $this->getTargetAudiences();
        if (in_array(self::AUDIENCE_ALL, $audiences, true)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        // Administrators and the creator/organizer can always view
        if ($user->hasRole('Administrator') || $this->organizer_id === $user->id) {
            return true;
        }

        $roleName = $user->role?->name;
        $userDeptCode = strtoupper($user->department?->code ?? '');

        foreach ($audiences as $audience) {
            if ($audience === self::AUDIENCE_FACULTY && $roleName === 'Faculty') {
                return true;
            }

            if ($audience === self::AUDIENCE_STAFF && in_array($roleName, self::STAFF_ROLES, true)) {
                return true;
            }

            if ($audience === self::AUDIENCE_STUDENT && $roleName === 'Student') {
                return true;
            }

            if (array_key_exists($audience, self::DEPARTMENT_AUDIENCES)) {
                $targetCode = strtoupper(self::DEPARTMENT_AUDIENCES[$audience]);
                if ($userDeptCode !== '' && $userDeptCode === $targetCode) {
                    return true;
                }
            }
        }

        return false;
    }

    public function canUserRegister(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Administrator')) {
            return true;
        }

        $audiences = $this->getTargetAudiences();
        if (in_array(self::AUDIENCE_ALL, $audiences, true)) {
            return true;
        }

        $roleName = $user->role?->name;
        $userDeptCode = strtoupper($user->department?->code ?? '');

        foreach ($audiences as $audience) {
            if ($audience === self::AUDIENCE_FACULTY && $roleName === 'Faculty') {
                return true;
            }

            if ($audience === self::AUDIENCE_STAFF && in_array($roleName, self::STAFF_ROLES, true)) {
                return true;
            }

            if ($audience === self::AUDIENCE_STUDENT && $roleName === 'Student') {
                return true;
            }

            if (array_key_exists($audience, self::DEPARTMENT_AUDIENCES)) {
                $targetCode = strtoupper(self::DEPARTMENT_AUDIENCES[$audience]);
                if ($userDeptCode !== '' && $userDeptCode === $targetCode) {
                    return true;
                }
            }
        }

        return false;
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['upcoming', 'ongoing']);
    }

    public function scopeVisibleTo($query, ?User $user)
    {
        if (!$user) {
            return $query->where(function ($q) {
                $q->whereNull('target_audience')
                  ->orWhere('target_audience', self::AUDIENCE_ALL)
                  ->orWhere('target_audience', 'like', '%"' . self::AUDIENCE_ALL . '"%');
            });
        }

        if ($user->hasRole('Administrator')) {
            return $query;
        }

        $roleName = $user->role?->name;
        $allowedAudiences = [self::AUDIENCE_ALL];

        if ($roleName === 'Faculty') {
            $allowedAudiences[] = self::AUDIENCE_FACULTY;
        } elseif (in_array($roleName, self::STAFF_ROLES, true)) {
            $allowedAudiences[] = self::AUDIENCE_STAFF;
        } elseif ($roleName === 'Student') {
            $allowedAudiences[] = self::AUDIENCE_STUDENT;
        }

        if ($user->department) {
            $userDeptCode = strtoupper($user->department->code);
            foreach (self::DEPARTMENT_AUDIENCES as $audKey => $deptCode) {
                if (strtoupper($deptCode) === $userDeptCode) {
                    $allowedAudiences[] = $audKey;
                    break;
                }
            }
        }

        return $query->where(function ($q) use ($allowedAudiences, $user) {
            $q->whereNull('target_audience')
              ->orWhere('organizer_id', $user->id);

            foreach ($allowedAudiences as $aud) {
                $q->orWhere('target_audience', $aud)
                  ->orWhere('target_audience', 'like', '%"' . $aud . '"%');
            }
        });
    }
}
