<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promo extends Model
{
    use HasUuids;

    protected $fillable = ['outlet_id', 'name', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'date', 'ends_at' => 'date'];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function discountFor(int $subtotal): int
    {
        if ($subtotal < $this->min_subtotal) {
            return 0;
        }
        $d = $this->type === 'percent' ? intdiv($subtotal * $this->value, 100) : $this->value;

        return min($d, $subtotal);
    }

    /** Promo aktif terbaik (diskon terbesar) untuk outlet & subtotal tertentu. */
    public static function bestFor(string $outletId, int $subtotal): ?array
    {
        $today = now()->toDateString();
        $best = null;
        $promos = static::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('outlet_id')->orWhere('outlet_id', $outletId))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today))
            ->get();
        foreach ($promos as $p) {
            $d = $p->discountFor($subtotal);
            if ($d > 0 && (! $best || $d > $best['amount'])) {
                $best = ['amount' => $d, 'label' => $p->name];
            }
        }

        return $best;
    }
}
