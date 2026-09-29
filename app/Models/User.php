<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_type', 'employee_id', 'first_name', 'last_name', 'email', 'password',
        'role_id', 'department_id', 'phone', 'avatar', 'status',
        'login_attempts', 'locked_until', 'last_login',
        'privacy_consent', 'privacy_consent_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'privacy_consent'      => 'boolean',
            'privacy_consent_date' => 'datetime',
            'last_login'           => 'datetime',
            'locked_until'         => 'datetime',
            'id_type'              => \App\Enums\UserIdType::class,
        ];
    }

    // ─── Relationships ───

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function facilityReservations(): HasMany
    {
        return $this->hasMany(FacilityReservation::class);
    }

    public function equipmentRequests(): HasMany
    {
        return $this->hasMany(EquipmentRequest::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ─── Accessors ───

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            mb_substr($this->first_name, 0, 1) . mb_substr($this->last_name, 0, 1)
        );
    }

    // ─── Permission Helpers ───

    /** @var string[]|null Memoized permission names for the current request. */
    private ?array $permissionCache = null;

    public function hasPermission(string $permissionName): bool
    {
        if ($this->permissionCache === null) {
            $this->permissionCache = $this->role
                ->permissions()
                ->pluck('name')
                ->all();
        }

        return in_array($permissionName, $this->permissionCache, true);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role->name === $roleName;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked' && $this->locked_until && $this->locked_until->isFuture();
    }

    // ─── Scopes ───

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdminsAndStaff($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->whereIn('name', ['Administrator', 'Staff']);
        })->active();
    }
}
