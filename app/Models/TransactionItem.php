<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionItem extends Model
{
    use HasUuids;

    protected $fillable = ['transaction_id', 'menu_id', 'variant_id', 'menu_name', 'size', 'qty', 'price_each', 'subtotal'];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(TransactionItemTopping::class);
    }
}
