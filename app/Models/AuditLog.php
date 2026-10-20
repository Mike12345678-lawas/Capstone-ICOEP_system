<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = ['user_id', 'action', 'table_name', 'record_id', 'ip_address', 'user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Log an audit action (replaces native logAudit() function).
     */
    public static function log(string $action, ?string $tableName = null, ?int $recordId = null, ?int $userId = null): self
    {
        return self::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'ip_address' => request() ? request()->ip() : '127.0.0.1',
            'user_agent' => request() ? request()->userAgent() : 'CLI/System',
        ]);
    }
}
