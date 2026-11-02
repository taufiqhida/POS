<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMovement extends Model
{
    use HasUuids;

    public const REASONS = ['restock' => 'Barang masuk', 'transfer' => 'Diambil outlet', 'adjustment' => 'Koreksi', 'waste' => 'Rusak / terbuang'];

    protected $fillable = ['warehouse_item_id', 'outlet_id', 'qty_change', 'reason', 'user_id', 'note'];

    protected $casts = ['qty_change' => 'float'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(WarehouseItem::class, 'warehouse_item_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
