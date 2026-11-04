<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promo extends Model
{
    use HasUuids;

    use Concerns\LogsChanges;

    public const AUDITED = ['name' => 'Nama', 'type' => 'Jenis', 'value' => 'Nilai', 'min_subtotal' => 'Min. belanja', 'is_active' => 'Aktif'];

    public const MONEY = ['min_subtotal'];

    public function auditLabel(): string
    {
        return 'Promo "'.$this->name.'"';
    }

    public const TYPES = ['percent' => 'Persen (%)', 'fixed' => 'Potongan (Rp)', 'free_item' => 'Beli item, gratis item'];

    protected $fillable = ['outlet_id', 'name', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'is_active',
        'trigger_categories', 'trigger_menus', 'buy_qty', 'reward_categories', 'reward_menus', 'reward_qty'];

    protected $casts = [
        'is_active' => 'boolean', 'starts_at' => 'date', 'ends_at' => 'date',
        'trigger_categories' => 'array', 'trigger_menus' => 'array', 'reward_categories' => 'array', 'reward_menus' => 'array',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function isFreeItem(): bool
    {
        return $this->type === 'free_item';
    }

    /** Promo yang sedang berlaku untuk outlet ini (aktif, dalam periode). */
    public static function activeFor(string $outletId): Builder
    {
        $today = now()->toDateString();

        return static::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('outlet_id')->orWhere('outlet_id', $outletId))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today));
    }

    /** @return Collection<int, Promo> */
    public static function freeItemPromos(string $outletId): Collection
    {
        return static::activeFor($outletId)->where('type', 'free_item')->where('buy_qty', '>', 0)->get();
    }

    public function matchesTrigger(Menu $menu): bool
    {
        return in_array($menu->category, $this->trigger_categories ?? [], true) || in_array($menu->name, $this->trigger_menus ?? [], true);
    }

    public function matchesReward(Menu $menu): bool
    {
        return in_array($menu->category, $this->reward_categories ?? [], true) || in_array($menu->name, $this->reward_menus ?? [], true);
    }

    /** Ringkasan untuk tabel & kasir, mis. "Beli 2 Waffle → gratis 1 Es Teh · Teh Series". */
    public function describe(): string
    {
        $join = fn (?array $a, ?array $b) => implode(' / ', [...($a ?? []), ...($b ?? [])]) ?: '—';

        return $this->isFreeItem()
            ? "Beli {$this->buy_qty} ".$join($this->trigger_categories, $this->trigger_menus)
                ." → gratis {$this->reward_qty} ".$join($this->reward_categories, $this->reward_menus)
            : ($this->type === 'percent' ? "{$this->value}%" : 'Rp'.number_format($this->value, 0, ',', '.'));
    }

    public function discountFor(int $subtotal): int
    {
        if ($this->isFreeItem() || $subtotal < $this->min_subtotal) {
            return 0;
        }
        $d = $this->type === 'percent' ? intdiv($subtotal * $this->value, 100) : $this->value;

        return min($d, $subtotal);
    }

    /** Promo diskon (persen/rupiah) terbaik untuk outlet & subtotal tertentu. */
    public static function bestFor(string $outletId, int $subtotal): ?array
    {
        $best = null;
        foreach (static::activeFor($outletId)->whereIn('type', ['percent', 'fixed'])->get() as $p) {
            $d = $p->discountFor($subtotal);
            if ($d > 0 && (! $best || $d > $best['amount'])) {
                $best = ['amount' => $d, 'label' => $p->name];
            }
        }

        return $best;
    }
}
