<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseItem extends Model
{
    use HasUuids;

    use Concerns\LogsChanges;

    public const AUDITED = ['name' => 'Nama', 'unit' => 'Satuan', 'stock_min' => 'Batas kritis'];

    public const MONEY = [];

    public function auditLabel(): string
    {
        return 'Bahan gudang "'.$this->name.'"';
    }

    protected $fillable = ['name', 'unit', 'stock_current', 'stock_min'];

    protected $casts = ['stock_current' => 'float', 'stock_min' => 'float'];

    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class);
    }

    public function isCritical(): bool
    {
        return $this->stock_current <= $this->stock_min;
    }

    public function scopeCritical(Builder $q): Builder
    {
        return $q->whereColumn('stock_current', '<=', 'stock_min');
    }
}
