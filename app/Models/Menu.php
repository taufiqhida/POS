<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasUuids;

    protected $fillable = ['outlet_id', 'name', 'category', 'image', 'base_price', 'free_toppings', 'topping_group', 'is_active', 'is_best'];

    protected $casts = ['is_active' => 'boolean', 'is_best' => 'boolean', 'base_price' => 'integer', 'free_toppings' => 'integer'];

    /**
     * ID menu "Best" untuk satu outlet: yang ditandai owner, atau — bila belum ada yang ditandai —
     * menu terlaris 30 hari terakhir. Mengembalikan [ids, 'manual'|'auto'].
     */
    public static function bestFor(string $outletId, int $limit = 8): array
    {
        $manual = static::where('outlet_id', $outletId)->where('is_active', true)->where('is_best', true)->pluck('id')->all();
        if ($manual) {
            return [$manual, 'manual'];
        }

        $auto = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->join('menus', 'menus.id', '=', 'transaction_items.menu_id')
            ->where('transactions.outlet_id', $outletId)->where('transactions.status', 'paid')
            ->where('transactions.created_at', '>=', now()->subDays(30))
            ->where('menus.is_active', true)
            ->groupBy('transaction_items.menu_id')
            ->orderByRaw('SUM(transaction_items.qty) DESC')
            ->limit($limit)->pluck('transaction_items.menu_id')->all();

        return [$auto, 'auto'];
    }

    public function imageUrl(): ?string
    {
        return $this->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image) : null;
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MenuVariant::class)->orderBy('price');
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
