<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Promo;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Topping;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    /**
     * Hitung ulang keranjang dari harga di database (harga dari klien tidak dipercaya).
     *
     * $cart: [['menu_id', 'variant_id', 'qty', 'topping_ids' => [], 'price_override' => ?int]]
     * $manualDiscount: diskon manual (butuh approver).
     */
    public function price(string $outletId, array $cart, int $manualDiscount = 0): array
    {
        $lines = [];
        $subtotal = 0;
        foreach ($cart as $row) {
            $menu = Menu::where('outlet_id', $outletId)->where('is_active', true)->findOrFail($row['menu_id']);
            $variant = ! empty($row['variant_id']) ? MenuVariant::where('menu_id', $menu->id)->findOrFail($row['variant_id']) : null;
            if (! $variant && $menu->variants()->exists()) {
                throw ValidationException::withMessages(['cart' => "Pilih ukuran untuk {$menu->name}."]);
            }
            $toppings = Topping::whereIn('id', $row['topping_ids'] ?? [])->where('is_active', true)
                ->where('group', $menu->topping_group)->get();
            $qty = max(1, (int) $row['qty']);
            $base = $variant?->price ?? $menu->base_price;
            $overridden = isset($row['price_override']) && $row['price_override'] !== null && (int) $row['price_override'] !== $base;
            if ($overridden) {
                $base = max(0, (int) $row['price_override']);
            }
            // Topping gratis: yang termahal digratiskan lebih dulu.
            $freeIds = $toppings->sortByDesc('price')->take($menu->free_toppings)->pluck('id')->all();
            $toppingPrice = fn (Topping $t) => in_array($t->id, $freeIds) ? 0 : $t->price;
            $each = $base + (int) $toppings->sum($toppingPrice);
            $lineTotal = $each * $qty;
            $subtotal += $lineTotal;
            $lines[] = compact('menu', 'variant', 'toppings', 'qty', 'each', 'lineTotal', 'overridden', 'base', 'freeIds');
        }

        $promo = Promo::bestFor($outletId, $subtotal);
        $discount = $promo['amount'] ?? 0;
        $label = $promo['label'] ?? null;
        if ($manualDiscount > 0) {
            $discount = min($subtotal, $discount + $manualDiscount);
            $label = trim(($label ? $label.' + ' : '').'Diskon manual');
        }

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'discount_label' => $label,
            'total' => $subtotal - $discount,
        ];
    }

    public function checkout(User $cashier, Shift $shift, array $cart, string $method, string $channel, int $paid, int $manualDiscount = 0, ?User $approver = null): Transaction
    {
        if ($shift->status !== 'open') {
            throw ValidationException::withMessages(['shift' => 'Shift belum dibuka.']);
        }
        if (empty($cart)) {
            throw ValidationException::withMessages(['cart' => 'Keranjang masih kosong.']);
        }
        if (! array_key_exists($method, Transaction::METHODS) || ! array_key_exists($channel, Transaction::CHANNELS)) {
            throw ValidationException::withMessages(['payment' => 'Metode bayar / kanal tidak valid.']);
        }

        $priced = $this->price($shift->outlet_id, $cart, $manualDiscount);
        $hasOverride = collect($priced['lines'])->contains('overridden', true);

        if ($hasOverride && ! ($approver?->canChangePrice())) {
            throw ValidationException::withMessages(['pin' => 'Ubah harga butuh PIN owner/manager yang berwenang.']);
        }
        if ($manualDiscount > 0 && ! ($approver?->canApprove())) {
            throw ValidationException::withMessages(['pin' => 'Diskon manual butuh PIN owner/manager.']);
        }
        if ($method === 'cash' && $channel === 'offline' && $paid < $priced['total']) {
            throw ValidationException::withMessages(['paid' => 'Uang yang dibayar kurang.']);
        }
        if ($method !== 'cash' || $channel !== 'offline') {
            $paid = $priced['total'];
        }

        return DB::transaction(function () use ($cashier, $shift, $priced, $method, $channel, $paid, $approver, $hasOverride, $manualDiscount) {
            $trx = Transaction::create([
                'number' => $this->nextNumber($shift->outlet_id),
                'outlet_id' => $shift->outlet_id,
                'shift_id' => $shift->id,
                'cashier_id' => $cashier->id,
                'payment_method' => $method,
                'channel' => $channel,
                'subtotal' => $priced['subtotal'],
                'discount' => $priced['discount'],
                'discount_label' => $priced['discount_label'],
                'total' => $priced['total'],
                'paid_amount' => $paid,
                'change_amount' => max(0, $paid - $priced['total']),
                'status' => 'paid',
            ]);

            foreach ($priced['lines'] as $l) {
                $item = $trx->items()->create([
                    'menu_id' => $l['menu']->id,
                    'variant_id' => $l['variant']?->id,
                    'menu_name' => $l['menu']->name,
                    'size' => $l['variant']?->size,
                    'qty' => $l['qty'],
                    'price_each' => $l['each'],
                    'subtotal' => $l['lineTotal'],
                ]);
                foreach ($l['toppings'] as $t) {
                    $free = in_array($t->id, $l['freeIds']);
                    $item->toppings()->create(['topping_id' => $t->id, 'topping_name' => $t->name.($free ? ' (gratis)' : ''),
                        'price_each' => $free ? 0 : $t->price]);
                }
                if ($l['overridden']) {
                    AuditLog::record('price_change', $cashier->id, 'transaction', $trx->id,
                        "{$l['menu']->name}: harga diubah jadi Rp".number_format($l['base'], 0, ',', '.'), $approver->id);
                }
            }

            if ($manualDiscount > 0) {
                AuditLog::record('discount', $cashier->id, 'transaction', $trx->id,
                    'Diskon manual Rp'.number_format($manualDiscount, 0, ',', '.'), $approver->id);
            }

            $this->applyStock($trx, -1, 'sale', $cashier->id);

            return $trx;
        });
    }

    /** Void (hari yang sama) atau refund. Stok bahan dikembalikan. */
    public function cancel(Transaction $trx, User $actor, User $approver, string $type, string $reason): Transaction
    {
        if (! $approver->canApprove()) {
            throw ValidationException::withMessages(['pin' => 'PIN owner/manager tidak valid.']);
        }
        if ($trx->status !== 'paid') {
            throw ValidationException::withMessages(['status' => 'Transaksi sudah dibatalkan sebelumnya.']);
        }

        return DB::transaction(function () use ($trx, $actor, $approver, $type, $reason) {
            $trx->update(['status' => $type === 'refund' ? 'refunded' : 'void']);
            $this->applyStock($trx, 1, 'void', $actor->id);
            AuditLog::record($type, $actor->id, 'transaction', $trx->id, "{$trx->number}: {$reason}", $approver->id);

            return $trx;
        });
    }

    /** Hitung kebutuhan bahan transaksi menurut resep standar: [ingredient_id => qty]. */
    public function ingredientUsage(Transaction $trx): array
    {
        $usage = [];
        $trx->loadMissing('items.toppings');
        $outletIngredients = Ingredient::where('outlet_id', $trx->outlet_id)->get()->keyBy(fn ($i) => mb_strtolower($i->name));

        foreach ($trx->items as $item) {
            $recipes = \App\Models\Recipe::where('menu_id', $item->menu_id)
                ->where(fn ($q) => $q->whereNull('variant_id')->orWhere('variant_id', $item->variant_id))
                ->get();
            foreach ($recipes as $r) {
                $usage[$r->ingredient_id] = ($usage[$r->ingredient_id] ?? 0) + $r->qty * $item->qty;
            }
            foreach ($item->toppings as $t) {
                foreach (\App\Models\ToppingRecipe::where('topping_id', $t->topping_id)->get() as $tr) {
                    $ing = $outletIngredients[mb_strtolower($tr->ingredient_name)] ?? null;
                    if ($ing) {
                        $usage[$ing->id] = ($usage[$ing->id] ?? 0) + $tr->qty * $item->qty;
                    }
                }
            }
        }

        return $usage;
    }

    private function applyStock(Transaction $trx, int $sign, string $reason, string $userId): void
    {
        foreach ($this->ingredientUsage($trx) as $ingredientId => $qty) {
            $change = $sign * $qty;
            Ingredient::whereKey($ingredientId)->increment('stock_current', $change);
            StockMovement::create([
                'outlet_id' => $trx->outlet_id, 'ingredient_id' => $ingredientId, 'qty_change' => $change,
                'reason' => $reason, 'reference_id' => $trx->id, 'user_id' => $userId, 'note' => $trx->number,
            ]);
        }
    }

    private function nextNumber(string $outletId): string
    {
        $prefix = 'JP'.now()->format('ymd');
        $count = Transaction::where('outlet_id', $outletId)->whereDate('created_at', today())->lockForUpdate()->count() + 1;
        $outletCode = strtoupper(substr(\App\Models\Outlet::find($outletId)->name, 0, 3));

        return sprintf('%s-%s-%04d', $prefix, $outletCode, $count);
    }
}
