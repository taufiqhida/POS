<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'approved_by', 'action', 'reference_type', 'reference_id', 'detail'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function record(string $action, ?string $userId, ?string $refType = null, ?string $refId = null, ?string $detail = null, ?string $approvedBy = null): self
    {
        return static::create([
            'user_id' => $userId, 'approved_by' => $approvedBy, 'action' => $action,
            'reference_type' => $refType, 'reference_id' => $refId, 'detail' => $detail,
        ]);
    }
}
