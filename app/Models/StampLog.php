<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampLog extends Model
{
    use HasUuids;

    public const REASONS = ['earn' => 'Dapat cap', 'redeem' => 'Tukar hadiah', 'reverse' => 'Dibatalkan (void/refund)',
        'expire' => 'Hangus', 'adjust' => 'Koreksi'];

    protected $fillable = ['customer_id', 'transaction_id', 'change', 'reason', 'user_id', 'note'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
