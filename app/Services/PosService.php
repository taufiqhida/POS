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
    /** Batas wajar input uang (Rp100 juta) — mencegah salah ketik & angka di luar kapasitas kolom database. */
    public const MAX_MONEY = 100_000_000;

    public const MAX_QTY = 999;

    /**
     * Hitung ulang keranjang dari harga di database (harga dari klien tidak dipercaya).
     *
     * $cart: [['menu_id', 'variant_id', 'qty', 'topping_ids' => [], 'price_override' => ?int, 'promo_id' => ?string]]
     * Baris dengan promo_id = item gratis dari promo "beli X gratis Y"; hanya sah sebanyak yang sudah didapat.
     * $manualDiscount: diskon manual (butuh approver).
     */
    public function price(string $outletId, array $cart, int $manualDiscount = 0): array
    {
        $built = [];
        $free = [];
        foreach ($cart as $i => $row) {
            if (! empty($row['promo_id'])) {
                $free[$i] = $row;
            } else {
                $built[$i] = $this->buildLine($outletId, $row);
            }
        }

        // Hadiah hanya dihitung dari item biasa (item gratis tidak ikut memicu promo).
        $earned = $this->earned(Promo::freeItemPromos($outletId), array_map(fn ($l) => ['menu' => $l['menu'], 'qty' => $l['qty']], $built));
        $used = [];
        foreach ($free as $i => $row) {
            $built[$i] = $this->buildFreeLine($outletId, $row, $earned, $used);
        }
        ksort($built);
        $lines = array_values($built);

        $subtotal = (int) array_sum(array_column($lines, 'lineTotal'));
        $promo = Promo::bestFor($outletId, $subtotal);
        $discount = $promo['amount'] ?? 0;
        $freeNames = collect($lines)->pluck('promo_name')->filter()->unique()->values()->all();
        $label = implode(' + ', array_filter([$promo['label'] ?? null, ...$freeNames])) ?: null;
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

    /** Satu baris pesanan biasa. */
    private function buildLine(string $outletId, array $row): array
    {
        $menu = Menu::where('outlet_id', $outletId)->where('is_active', true)->find($row['menu_id']);
        if (! $menu) {
            throw ValidationException::withMessages(['cart' => ($row['name'] ?? 'Menu').' sudah tidak tersedia. Hapus dari pesanan.']);
        }
        $variant = ! empty($row['variant_id']) ? MenuVariant::where('menu_id', $menu->id)->find($row['variant_id']) : null;
        if (! empty($row['variant_id']) && ! $variant) {
            throw ValidationException::withMessages(['cart' => "Ukuran {$menu->name} sudah tidak tersedia. Hapus dari pesanan."]);
        }
        $qty = (int) $row['qty'];
        if ($qty < 1 || $qty > self::MAX_QTY) {
            throw ValidationException::withMessages(['cart' => "Jumlah {$menu->name} harus 1–".self::MAX_QTY.'.']);
        }
        if (! $variant && $menu->variants()->exists()) {
            throw ValidationException::withMessages(['cart' => "Pilih ukuran untuk {$menu->name}."]);
        }
        $toppings = Topping::whereIn('id', $row['topping_ids'] ?? [])->where('is_active', true)
            ->where('group', $menu->topping_group)->get();
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

        return ['menu' => $menu, 'variant' => $variant, 'toppings' => $toppings, 'qty' => $qty, 'each' => $each,
            'lineTotal' => $lineTotal, 'overridden' => $overridden, 'base' => $base, 'freeIds' => $freeIds,
            'promo_name' => null, 'listPrice' => null];
    }

    /** Baris item gratis dari promo: harga Rp0, tanpa topping, dan tidak boleh melebihi yang sudah didapat. */
    private function buildFreeLine(string $outletId, array $row, array $earned, array &$used): array
    {
        $promo = Promo::freeItemPromos($outletId)->firstWhere('id', $row['promo_id']);
        $menu = Menu::where('outlet_id', $outletId)->where('is_active', true)->find($row['menu_id']);
        if (! $promo || ! $menu || ! $promo->matchesReward($menu)) {
            throw ValidationException::withMessages(['cart' => 'Promo gratisan sudah tidak berlaku. Hapus item gratis dari pesanan.']);
        }
        $variant = ! empty($row['variant_id']) ? MenuVariant::where('menu_id', $menu->id)->find($row['variant_id']) : null;
        if ($menu->variants()->exists() && ! $variant) {
            throw ValidationException::withMessages(['cart' => "Pilih ukuran untuk {$menu->name}."]);
        }
        $qty = (int) $row['qty'];
        $used[$promo->id] = ($used[$promo->id] ?? 0) + $qty;
        if ($qty < 1 || $used[$promo->id] > ($earned[$promo->id] ?? 0)) {
            throw ValidationException::withMessages(['cart' => "Promo \"{$promo->name}\" hanya memberi ".($earned[$promo->id] ?? 0).' gratisan.']);
        }

        return ['menu' => $menu, 'variant' => $variant, 'toppings' => collect(), 'qty' => $qty, 'each' => 0, 'lineTotal' => 0,
            'overridden' => false, 'base' => 0, 'freeIds' => [], 'promo_name' => $promo->name,
            'listPrice' => $variant?->price ?? $menu->base_price];
    }

    /**
     * Berapa gratisan yang didapat tiap promo dari item biasa di pesanan.
     *
     * @param  iterable<Promo>  $promos
     * @param  array<int, array{menu: Menu, qty: int}>  $items
     * @return array<string, int> promo_id => jumlah gratisan
     */
    private function earned(iterable $promos, array $items): array
    {
        $out = [];
        foreach ($promos as $p) {
            $count = 0;
            foreach ($items as $it) {
                if ($p->matchesTrigger($it['menu'])) {
                    $count += $it['qty'];
                }
            }
            $out[$p->id] = intdiv($count, max(1, $p->buy_qty)) * max(1, $p->reward_qty);
        }

        return $out;
    }

    /**
     * Status promo "beli X gratis Y" untuk pesanan saat ini (dipakai layar kasir).
     *
     * @return array<int, array{promo: Promo, count: int, need: int, earned: int, used: int, remaining: int, menus: \Illuminate\Support\Collection}>
     */
    public function offers(string $outletId, array $cart): array
    {
        $promos = Promo::freeItemPromos($outletId);
        if ($promos->isEmpty()) {
            return [];
        }
        $menus = Menu::with('variants')->where('outlet_id', $outletId)->where('is_active', true)->get();
        $byId = $menus->keyBy('id');
        $items = [];
        $used = [];
        foreach ($cart as $row) {
            if (! empty($row['promo_id'])) {
                $used[$row['promo_id']] = ($used[$row['promo_id']] ?? 0) + (int) $row['qty'];
            } elseif ($m = $byId[$row['menu_id']] ?? null) {
                $items[] = ['menu' => $m, 'qty' => (int) $row['qty']];
            }
        }
        $earned = $this->earned($promos, $items);

        $offers = [];
        foreach ($promos as $p) {
            $count = collect($items)->filter(fn ($it) => $p->matchesTrigger($it['menu']))->sum('qty');
            $remaining = ($earned[$p->id] ?? 0) - ($used[$p->id] ?? 0);
            $offers[] = [
                'promo' => $p, 'count' => $count, 'earned' => $earned[$p->id] ?? 0, 'used' => $used[$p->id] ?? 0,
                'remaining' => max(0, $remaining),
                'need' => $count > 0 ? max(1, $p->buy_qty) - ($count % max(1, $p->buy_qty)) : 0,
                'menus' => $remaining > 0 ? $menus->filter(fn ($m) => $p->matchesReward($m))->values() : collect(),
            ];
        }

        return $offers;
    }

    /**
     * Buang/kurangi item gratis yang sudah tidak berhak (item pemicu dikurangi, promo dimatikan, dsb).
     *
     * @return array{0: array, 1: array<int, string>} [keranjang baru, nama item yang dikeluarkan]
     */
    public function pruneFreeLines(string $outletId, array $cart): array
    {
        $promos = Promo::freeItemPromos($outletId)->keyBy('id');
        $menus = Menu::where('outlet_id', $outletId)->whereIn('id', array_column($cart, 'menu_id'))->get()->keyBy('id');
        $items = [];
        foreach ($cart as $row) {
            if (empty($row['promo_id']) && isset($menus[$row['menu_id']])) {
                $items[] = ['menu' => $menus[$row['menu_id']], 'qty' => (int) $row['qty']];
            }
        }
        $earned = $this->earned($promos, $items);

        $removed = [];
        $used = [];
        foreach ($cart as $key => $row) {
            if (empty($row['promo_id'])) {
                continue;
            }
            $p = $promos[$row['promo_id']] ?? null;
            $m = $menus[$row['menu_id']] ?? null;
            $room = $p ? ($earned[$p->id] ?? 0) - ($used[$p->id] ?? 0) : 0;
            if (! $p || ! $m || ! $p->matchesReward($m) || $room <= 0) {
                $removed[] = $row['name'];
                unset($cart[$key]);

                continue;
            }
            if ($row['qty'] > $room) {
                $cart[$key]['qty'] = $room;
                $removed[] = ($row['qty'] - $room).'× '.$row['name'];
            }
            $used[$p->id] = ($used[$p->id] ?? 0) + $cart[$key]['qty'];
        }

        return [$cart, $removed];
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
        if ($paid > self::MAX_MONEY || $manualDiscount > self::MAX_MONEY) {
            throw ValidationException::withMessages(['paid' => 'Nominal terlalu besar. Periksa lagi angkanya.']);
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
                    'promo_name' => $l['promo_name'],
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

    /** Kasir mengajukan void/refund. Transaksi tetap dihitung sampai disetujui owner/manager. */
    public function requestCancel(Transaction $trx, User $cashier, string $type, string $reason): Transaction
    {
        if (! in_array($type, ['void', 'refund'])) {
            throw ValidationException::withMessages(['type' => 'Jenis pengajuan tidak valid.']);
        }
        if ($trx->status !== 'paid') {
            throw ValidationException::withMessages(['status' => 'Transaksi sudah dibatalkan sebelumnya.']);
        }
        if ($trx->cancel_request) {
            throw ValidationException::withMessages(['status' => 'Transaksi ini sudah diajukan dan menunggu verifikasi.']);
        }

        $trx->update(['cancel_request' => $type, 'cancel_reason' => $reason,
            'cancel_requested_by' => $cashier->id, 'cancel_requested_at' => now()]);
        AuditLog::record($type.'_request', $cashier->id, 'transaction', $trx->id, "{$trx->number}: {$reason}");

        return $trx;
    }

    /** Owner/manager menyetujui pengajuan: transaksi dibatalkan & stok dikembalikan. */
    public function approveCancel(Transaction $trx, User $approver): Transaction
    {
        if (! $trx->isPendingCancel()) {
            throw ValidationException::withMessages(['status' => 'Tidak ada pengajuan yang menunggu untuk transaksi ini.']);
        }

        return $this->cancel($trx, $trx->requester, $approver, $trx->cancel_request, $trx->cancel_reason);
    }

    /** Owner/manager menolak pengajuan: transaksi tetap sah. */
    public function rejectCancel(Transaction $trx, User $approver, ?string $note = null): Transaction
    {
        if (! $approver->canApprove()) {
            throw ValidationException::withMessages(['status' => 'Hanya owner/manager yang bisa memverifikasi.']);
        }
        if (! $trx->isPendingCancel()) {
            throw ValidationException::withMessages(['status' => 'Tidak ada pengajuan yang menunggu untuk transaksi ini.']);
        }
        AuditLog::record($trx->cancel_request.'_rejected', $trx->cancel_requested_by, 'transaction', $trx->id,
            "{$trx->number}: ditolak".($note ? " — {$note}" : ''), $approver->id);
        $trx->update(['cancel_request' => null, 'cancel_reason' => null, 'cancel_requested_by' => null, 'cancel_requested_at' => null]);

        return $trx;
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
