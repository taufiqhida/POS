<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['outlet_id', 'name', 'category', 'image', 'base_price', 'free_toppings', 'stamps', 'topping_group', 'is_active', 'is_best', 'is_reward'];

    protected $casts = ['is_active' => 'boolean', 'is_best' => 'boolean', 'is_reward' => 'boolean', 'stamps' => 'integer', 'base_price' => 'integer', 'free_toppings' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (Menu $m) {
            if (! array_key_exists('stamps', $m->getAttributes())) {
                $m->stamps = static::defaultStamps($m->category, (int) $m->base_price);
            }
        });
    }

    /** Cap per item: makanan berat 3, minuman Rp10.000 ke atas 2, selebihnya 1. Owner bisa mengubah per menu. */
    public static function defaultStamps(?string $category, int $price): int
    {
        if (preg_match('/waffle|kebab|burger|maryam/i', (string) $category)) {
            return 3;
        }

        return $price >= 10000 ? 2 : 1;
    }

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
            ->where('menus.is_active', true)->whereNull('menus.deleted_at')
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
