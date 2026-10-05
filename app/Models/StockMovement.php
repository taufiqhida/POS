<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    public const REASONS = ['sale' => 'Penjualan', 'restock' => 'Restock', 'adjustment' => 'Koreksi', 'waste' => 'Waste', 'void' => 'Batal/Refund'];

    protected $fillable = ['outlet_id', 'ingredient_id', 'qty_change', 'reason', 'reference_id', 'user_id', 'note'];

    protected $casts = ['qty_change' => 'float'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
