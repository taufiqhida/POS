<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasUuids;

    public const METHODS = ['cash' => 'Tunai', 'qris' => 'QRIS', 'debit' => 'Debit', 'digital' => 'Digital Lain'];

    public const CHANNELS = ['offline' => 'Kasir Offline', 'gofood' => 'GoFood', 'grabfood' => 'GrabFood', 'shopeefood' => 'ShopeeFood'];

    public const STATUSES = ['paid' => 'Lunas', 'void' => 'Void', 'refunded' => 'Refund'];

    protected $fillable = ['number', 'outlet_id', 'shift_id', 'cashier_id', 'payment_method', 'channel', 'subtotal',
        'discount', 'discount_label', 'total', 'paid_amount', 'change_amount', 'status'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
