<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasUuids;

    use Concerns\LogsChanges;

    public const AUDITED = ['name' => 'Nama', 'unit' => 'Satuan', 'stock_current' => 'Stok', 'stock_min' => 'Batas kritis'];

    public const MONEY = [];

    public function auditLabel(): string
    {
        return 'Bahan "'.$this->name.'" ('.($this->outlet?->name ?? '—').')';
    }

    protected $fillable = ['outlet_id', 'name', 'unit', 'stock_current', 'stock_min'];

    protected $casts = ['stock_current' => 'float', 'stock_min' => 'float'];

    protected static function booted(): void
    {
        // Bahan baru di outlet otomatis punya kartu di gudang utama (stok awal 0).
        static::created(fn (Ingredient $i) => WarehouseItem::firstOrCreate(['name' => $i->name], ['unit' => $i->unit]));
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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
