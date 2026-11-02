<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public const MAX_QTY = 1_000_000;

    /** Barang masuk ke gudang (pembelian / restock dari supplier). */
    public function receive(WarehouseItem $item, float $qty, User $user, ?string $note = null): WarehouseItem
    {
        $this->authorize($user);
        $qty = $this->checkQty($qty);

        return DB::transaction(function () use ($item, $qty, $user, $note) {
            $item = WarehouseItem::lockForUpdate()->findOrFail($item->id);
            $item->increment('stock_current', $qty);
            $this->log($item, $qty, 'restock', $user, null, $note);

            return $item->refresh();
        });
    }

    /** Koreksi gudang ke jumlah fisik sebenarnya, atau catat barang rusak/terbuang. */
    public function adjust(WarehouseItem $item, float $physical, User $user, ?string $note = null, bool $asWaste = false): WarehouseItem
    {
        $this->authorize($user);
        if ($physical < 0 || $physical > self::MAX_QTY) {
            throw ValidationException::withMessages(['qty' => 'Jumlah harus 0 sampai '.number_format(self::MAX_QTY, 0, ',', '.').'.']);
        }

        return DB::transaction(function () use ($item, $physical, $user, $note, $asWaste) {
            $item = WarehouseItem::lockForUpdate()->findOrFail($item->id);
            $diff = round($physical - $item->stock_current, 2);
            if ($diff != 0) {
                $item->update(['stock_current' => $physical]);
                $this->log($item, $diff, $asWaste && $diff < 0 ? 'waste' : 'adjustment', $user, null, $note);
            }

            return $item->refresh();
        });
    }

    /**
     * Outlet mengambil bahan dari gudang: stok gudang berkurang, stok bahan di outlet bertambah.
     * Bahan di outlet dicocokkan lewat nama; dibuat otomatis bila outlet belum punya.
     */
    public function transfer(WarehouseItem $item, Outlet $outlet, float $qty, User $user, ?string $note = null): Ingredient
    {
        $this->authorize($user);
        $qty = $this->checkQty($qty);

        return DB::transaction(function () use ($item, $outlet, $qty, $user, $note) {
            $item = WarehouseItem::lockForUpdate()->findOrFail($item->id);
            if ($item->stock_current < $qty) {
                throw ValidationException::withMessages(['qty' => sprintf('Stok gudang %s hanya %s %s.', $item->name,
                    rtrim(rtrim(number_format($item->stock_current, 2, ',', '.'), '0'), ','), $item->unit)]);
            }

            $ingredient = Ingredient::where('outlet_id', $outlet->id)->where('name', $item->name)->first();
            if ($ingredient && $ingredient->unit !== $item->unit) {
                throw ValidationException::withMessages(['qty' => "Satuan {$item->name} di gudang ({$item->unit}) berbeda dengan di {$outlet->name} ({$ingredient->unit}). Samakan dulu di panel admin."]);
            }
            $ingredient ??= Ingredient::create(['outlet_id' => $outlet->id, 'name' => $item->name, 'unit' => $item->unit,
                'stock_current' => 0, 'stock_min' => 0]);

            $item->decrement('stock_current', $qty);
            $ingredient->increment('stock_current', $qty);

            $this->log($item, -$qty, 'transfer', $user, $outlet, $note);
            StockMovement::create(['outlet_id' => $outlet->id, 'ingredient_id' => $ingredient->id, 'qty_change' => $qty,
                'reason' => 'transfer', 'user_id' => $user->id, 'note' => trim('Dari gudang '.$note)]);

            return $ingredient->refresh();
        });
    }

    /** Gudang utama hanya boleh dikelola owner & manager — kasir tidak boleh mengambil/mengubah stok gudang. */
    private function authorize(User $user): void
    {
        if (! $user->canApprove()) {
            throw ValidationException::withMessages(['qty' => 'Hanya owner atau manager yang boleh mengambil atau mengubah stok gudang.']);
        }
    }

    private function checkQty(float $qty): float
    {
        if ($qty <= 0 || $qty > self::MAX_QTY) {
            throw ValidationException::withMessages(['qty' => 'Jumlah harus lebih dari 0 dan paling banyak '.number_format(self::MAX_QTY, 0, ',', '.').'.']);
        }

        return round($qty, 2);
    }

    private function log(WarehouseItem $item, float $change, string $reason, User $user, ?Outlet $outlet, ?string $note): void
    {
        WarehouseMovement::create(['warehouse_item_id' => $item->id, 'outlet_id' => $outlet?->id, 'qty_change' => $change,
            'reason' => $reason, 'user_id' => $user->id, 'note' => $note]);
    }
}
