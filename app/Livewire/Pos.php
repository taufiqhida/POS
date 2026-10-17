<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Shift;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Topping;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ReportService;
use App\Services\ShiftService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
class Pos extends Component
{
    public string $outletId;

    public string $tab = 'kasir';

    public string $category = '';

    public string $search = '';

    // Keranjang
    public array $cart = [];

    public ?string $pickMenuId = null;

    public ?string $pickVariantId = null;

    public array $pickToppings = [];

    // Pembayaran
    public string $method = 'cash';

    public string $channel = 'offline';

    public ?int $paid = null;

    public int $manualDiscount = 0;

    public ?string $approverId = null;

    public bool $showPay = false;

    /** Di HP: panel pesanan dibuka sebagai lembar dari bawah. */
    public bool $cartOpen = false;

    public ?string $lastTrxId = null;

    // PIN approval
    public ?string $pinAction = null;

    public array $pinContext = [];

    public string $pin = '';

    public ?int $pinValue = null;

    public string $pinReason = '';

    // Shift
    public ?int $openingCash = null;

    public string $expenseDesc = '';

    public ?int $expenseAmount = null;

    public ?int $actualCash = null;

    public bool $confirmingClose = false;

    public string $closeNote = '';

    public ?string $closedShiftId = null;

    // Stok
    public array $physical = [];

    public function mount(): void
    {
        $this->outletId = session('pos_outlet_id');
        // Mulai dari tab ⭐ Best supaya menu yang paling sering dipesan langsung terlihat.
        $this->category = $this->best[0] ? self::BEST : '';
    }

    #[Computed]
    public function outlet(): Outlet
    {
        return Outlet::findOrFail($this->outletId);
    }

    #[Computed]
    public function shift(): ?Shift
    {
        return $this->outlet->openShift();
    }

    #[Computed]
    public function menus()
    {
        // Pencarian selalu ke semua menu, termasuk saat tab Best aktif.
        $best = $this->category === self::BEST && $this->search === '';

        $menus = Menu::with('variants')->where('outlet_id', $this->outletId)->where('is_active', true)
            ->when($best, fn ($q) => $q->whereIn('id', $this->best[0]))
            ->when($this->category && $this->category !== self::BEST, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('category')->orderBy('name')->get();

        // Urutan otomatis mengikuti peringkat penjualan.
        return $best ? $menus->sortBy(fn ($m) => array_search($m->id, $this->best[0]))->values() : $menus;
    }

    /**
     * ID menu yang bahannya tidak cukup untuk 1 porsi (stok habis). Menu dengan ukuran dianggap habis
     * bila bahan umumnya habis, atau semua ukurannya kekurangan bahan khusus ukuran (mis. cup).
     *
     * @return array<string, true>
     */
    #[Computed]
    public function outOfStock(): array
    {
        $rows = \App\Models\Recipe::query()
            ->join('menus', 'menus.id', '=', 'recipes.menu_id')
            ->join('ingredients', 'ingredients.id', '=', 'recipes.ingredient_id')
            ->where('menus.outlet_id', $this->outletId)->whereNull('menus.deleted_at')
            ->whereColumn('ingredients.stock_current', '<', 'recipes.qty')
            ->get(['recipes.menu_id', 'recipes.variant_id']);
        $sizes = \App\Models\MenuVariant::whereIn('menu_id', $rows->pluck('menu_id')->unique())->get()->groupBy('menu_id');

        $out = [];
        foreach ($rows->groupBy('menu_id') as $menuId => $lacking) {
            $general = $lacking->contains(fn ($r) => $r->variant_id === null);
            $allSizes = ($sizes[$menuId] ?? collect())->isNotEmpty()
                && ($sizes[$menuId])->every(fn ($v) => $lacking->contains('variant_id', $v->id));
            if ($general || $allSizes) {
                $out[$menuId] = true;
            }
        }

        return $out;
    }

    /** Kategori khusus untuk tab ⭐ Best. */
    public const BEST = '__best';

    /** @return array{0: array<int, string>, 1: string} [menu ids, 'manual'|'auto'] */
    #[Computed]
    public function best(): array
    {
        return Menu::bestFor($this->outletId);
    }

    #[Computed]
    public function categories()
    {
        return Menu::where('outlet_id', $this->outletId)->where('is_active', true)->distinct()->orderBy('category')->pluck('category');
    }

    #[Computed]
    public function toppings()
    {
        return Topping::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function priced(): array
    {
        $empty = ['lines' => [], 'subtotal' => 0, 'discount' => 0, 'discount_label' => null, 'total' => 0];
        $this->pruneCart();
        if (empty($this->cart)) {
            return $empty;
        }

        return app(PosService::class)->price($this->outletId, array_values($this->cart), $this->manualDiscount);
    }

    /**
     * Keluarkan item yang menunya baru saja dinonaktifkan/dihapus owner (atau ukurannya dihapus),
     * supaya layar kasir tidak error dan kasir tahu kenapa item hilang.
     */
    private function pruneCart(): void
    {
        if (empty($this->cart)) {
            return;
        }
        $menus = Menu::with('variants')->where('outlet_id', $this->outletId)->where('is_active', true)
            ->whereIn('id', array_column($this->cart, 'menu_id'))->get()->keyBy('id');
        $removed = [];
        foreach ($this->cart as $key => $line) {
            $menu = $menus[$line['menu_id']] ?? null;
            $sizeGone = $menu && ($line['variant_id']
                ? ! $menu->variants->contains('id', $line['variant_id'])
                : $menu->variants->isNotEmpty());
            if (! $menu || $sizeGone) {
                $removed[] = $line['name'];
                unset($this->cart[$key]);
            }
        }
        if ($removed) {
            $this->afterCartShrink();
            $this->toast(implode(', ', $removed).' sudah tidak tersedia dan dikeluarkan dari pesanan.', 'error');
        }
    }

    /** Pesanan kosong = pelanggan baru: diskon manual & persetujuan PIN tidak boleh terbawa. */
    private function afterCartShrink(): void
    {
        if (empty($this->cart)) {
            $this->reset('manualDiscount', 'approverId', 'cartOpen');
        }
    }

    // ---------- Shift ----------

    public function openShift(ShiftService $svc): void
    {
        $this->validate(['openingCash' => 'required|integer|min:0|max:'.PosService::MAX_MONEY], [], ['openingCash' => 'modal awal']);
        $svc->open(Auth::user(), $this->outlet, $this->openingCash);
        $this->openingCash = null;
        $this->closedShiftId = null;
        unset($this->shift);
        $this->toast('Shift dibuka. Selamat bekerja! 🧋');
    }

    public function addExpense(): void
    {
        $this->validate(['expenseDesc' => 'required|string|max:200', 'expenseAmount' => 'required|integer|min:1|max:'.PosService::MAX_MONEY], [], ['expenseDesc' => 'keterangan', 'expenseAmount' => 'jumlah']);
        $this->requireShift()->expenses()->create(['description' => $this->expenseDesc, 'amount' => $this->expenseAmount, 'created_by' => Auth::id()]);
        AuditLog::record('expense', Auth::id(), 'shift', $this->shift->id, "{$this->expenseDesc}: Rp".number_format($this->expenseAmount, 0, ',', '.'));
        $this->reset('expenseDesc', 'expenseAmount');
        $this->toast('Pengeluaran dicatat.');
    }

    /** Langkah 1: cek isian, lalu tampilkan konfirmasi di dalam aplikasi (bukan confirm() bawaan browser). */
    public function confirmCloseShift(): void
    {
        $this->validate(['actualCash' => 'required|integer|min:0|max:'.PosService::MAX_MONEY], [], ['actualCash' => 'cash fisik']);
        $this->confirmingClose = true;
    }

    public function closeShift(ShiftService $svc): void
    {
        $this->validate(['actualCash' => 'required|integer|min:0|max:'.PosService::MAX_MONEY], [], ['actualCash' => 'cash fisik']);
        $this->confirmingClose = false;
        $shift = $svc->close($this->requireShift(), Auth::user(), $this->actualCash, $this->closeNote ?: null);
        $this->closedShiftId = $shift->id;
        $this->reset('actualCash', 'closeNote', 'cart');
        unset($this->shift);
    }

    // ---------- Keranjang ----------

    public function pick(string $menuId): void
    {
        $menu = $this->menus->firstWhere('id', $menuId);
        if (! $menu) {
            return;
        }
        $this->pickMenuId = $menuId;
        // Default ukuran normal (Reguler/Regular); ukuran lain dipilih manual.
        $this->pickVariantId = ($menu->variants->first(fn ($v) => in_array($v->size, ['Reguler', 'Regular'])) ?? $menu->variants->first())?->id;
        $this->pickToppings = [];
    }

    public function toggleTopping(string $id): void
    {
        // Hanya topping dari grup menu yang sedang dipilih (mis. topping minuman untuk minuman).
        $group = Menu::find($this->pickMenuId)?->topping_group;
        if (! in_array($id, $this->pickToppings) && ! $this->toppings->where('group', $group)->contains('id', $id)) {
            return;
        }
        $this->pickToppings = in_array($id, $this->pickToppings)
            ? array_values(array_diff($this->pickToppings, [$id]))
            : [...$this->pickToppings, $id];
    }

    public function addToCart(): void
    {
        $menu = Menu::with('variants')->findOrFail($this->pickMenuId);
        $variant = $menu->variants->firstWhere('id', $this->pickVariantId);
        $toppings = $this->toppings->whereIn('id', $this->pickToppings);
        sort($this->pickToppings);
        $sig = $menu->id.'|'.$this->pickVariantId.'|'.implode(',', $this->pickToppings);

        foreach ($this->cart as $k => $line) {
            if ($line['sig'] === $sig && $line['price_override'] === null) {
                $this->cart[$k]['qty']++;
                $this->pickMenuId = null;

                return;
            }
        }
        $this->cart[(string) Str::uuid()] = [
            'sig' => $sig,
            'menu_id' => $menu->id,
            'variant_id' => $variant?->id,
            'topping_ids' => $this->pickToppings,
            'qty' => 1,
            'price_override' => null,
            'name' => $menu->name,
            'size' => $variant?->size,
            'topping_names' => $toppings->pluck('name')->implode(', '),
        ];
        $this->pickMenuId = null;
    }

    public function qty(string $key, int $delta): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }
        $this->cart[$key]['qty'] += $delta;
        if ($this->cart[$key]['qty'] < 1) {
            unset($this->cart[$key]);
        }
        $this->afterCartShrink();
    }

    public function removeLine(string $key): void
    {
        unset($this->cart[$key]);
        $this->afterCartShrink();
    }

    public function clearCart(): void
    {
        $this->reset('cart', 'manualDiscount', 'approverId', 'paid', 'showPay', 'cartOpen');
        $this->channel = 'offline';
        $this->method = 'cash';
    }

    // ---------- PIN owner/manager ----------

    public function askPin(string $action, array $context = []): void
    {
        $this->pinAction = $action;
        $this->pinContext = $context;
        $this->reset('pin', 'pinValue', 'pinReason');
        $this->resetErrorBag();
    }

    public function cancelPin(): void
    {
        $this->pinAction = null;
    }

    public function confirmPin(PosService $pos): void
    {
        $approver = $this->findApprover($this->pin);
        if (! $approver) {
            $this->addError('pin', 'PIN owner/manager tidak valid.');
            $this->pin = '';

            return;
        }

        switch ($this->pinAction) {
            case 'price':
                if (! $approver->canChangePrice()) {
                    $this->addError('pin', "{$approver->name} tidak punya hak ubah harga.");

                    return;
                }
                $this->validate(['pinValue' => 'required|integer|min:0|max:'.PosService::MAX_MONEY], [], ['pinValue' => 'harga baru']);
                $this->cart[$this->pinContext['key']]['price_override'] = $this->pinValue;
                $this->cart[$this->pinContext['key']]['sig'] .= '|o';
                $this->approverId = $approver->id;
                break;
            case 'discount':
                $this->validate(['pinValue' => 'required|integer|min:0|max:'.PosService::MAX_MONEY], [], ['pinValue' => 'diskon']);
                $this->manualDiscount = $this->pinValue;
                $this->approverId = $approver->id;
                break;
        }
        $this->pinAction = null;
    }

    // ---------- Pengajuan void/refund (diverifikasi owner/manager di panel admin) ----------

    public ?string $cancelId = null;

    public string $cancelType = 'void';

    public string $cancelReason = '';

    public function askCancel(string $id, string $type): void
    {
        $this->cancelId = $id;
        $this->cancelType = $type === 'refund' ? 'refund' : 'void';
        $this->cancelReason = '';
        $this->resetErrorBag();
    }

    public function submitCancelRequest(PosService $pos): void
    {
        $this->validate(['cancelReason' => 'required|string|max:200'], [], ['cancelReason' => 'alasan']);
        $trx = Transaction::where('outlet_id', $this->outletId)->findOrFail($this->cancelId);
        try {
            $pos->requestCancel($trx, Auth::user(), $this->cancelType, $this->cancelReason);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }
        $this->cancelId = null;
        unset($this->history);
        $this->toast("Pengajuan {$this->cancelType} {$trx->number} terkirim. Menunggu verifikasi owner/manager.");
    }

    private function findApprover(string $pin): ?User
    {
        if ($pin === '') {
            return null;
        }

        return User::where('is_active', true)->whereIn('role', ['owner', 'manager'])->get()
            ->first(fn (User $u) => $u->checkPin($pin));
    }

    // ---------- Bayar ----------

    public function openPay(): void
    {
        if (empty($this->cart)) {
            return;
        }
        $this->showPay = true;
        $this->cartOpen = false;
        $this->paid = null;
    }

    public function checkout(PosService $pos): void
    {
        $approver = $this->approverId ? User::find($this->approverId) : null;
        try {
            $trx = $pos->checkout(Auth::user(), $this->requireShift(), array_values($this->cart), $this->method, $this->channel,
                (int) $this->paid, $this->manualDiscount, $approver);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }
        $this->lastTrxId = $trx->id;
        $this->clearCart();
        unset($this->priced);
    }

    #[Computed]
    public function lastTrx(): ?Transaction
    {
        return $this->lastTrxId ? Transaction::find($this->lastTrxId) : null;
    }

    public function newOrder(): void
    {
        $this->lastTrxId = null;
    }

    // ---------- Riwayat ----------

    #[Computed]
    public function history()
    {
        return Transaction::with(['cashier', 'items', 'requester'])->where('outlet_id', $this->outletId)
            ->whereDate('created_at', today())->latest()->limit(100)->get();
    }

    // ---------- Stok ----------

    #[Computed]
    public function ingredients()
    {
        return Ingredient::where('outlet_id', $this->outletId)->orderBy('name')->get();
    }

    #[Computed]
    public function usageHistory()
    {
        return StockMovement::with(['ingredient', 'user'])->where('outlet_id', $this->outletId)->latest()->limit(50)->get();
    }

    public function saveStockCount(): void
    {
        // Kolom yang dikosongkan = sudah dicek & sesuai sistem; hanya yang berbeda perlu diketik.
        foreach ($this->physical as $id => $v) {
            if ($v !== '' && $v !== null && (! is_numeric($v) || $v < 0 || $v > 1_000_000)) {
                $this->toast('Isi hitungan fisik dengan angka 0 sampai 1.000.000.', 'error');

                return;
            }
        }
        $changed = 0;
        DB::transaction(function () use (&$changed) {
            foreach ($this->ingredients as $ing) {
                $v = $this->physical[$ing->id] ?? null;
                $qty = ($v === '' || $v === null) ? $ing->stock_current : (float) $v;
                $changed += $qty != $ing->stock_current ? 1 : 0;
                $diff = (float) $qty - $ing->stock_current;
                StockCount::create([
                    'outlet_id' => $this->outletId, 'ingredient_id' => $ing->id, 'user_id' => Auth::id(),
                    'system_qty' => $ing->stock_current, 'physical_qty' => $qty, 'difference' => $diff,
                ]);
                if ($diff != 0) {
                    StockMovement::create([
                        'outlet_id' => $this->outletId, 'ingredient_id' => $ing->id, 'qty_change' => $diff,
                        'reason' => $diff < 0 ? 'waste' : 'adjustment', 'user_id' => Auth::id(), 'note' => 'Cek stok fisik',
                    ]);
                    $ing->update(['stock_current' => $qty]);
                }
            }
        });
        $this->physical = [];
        unset($this->ingredients, $this->usageHistory);
        $this->toast($changed
            ? "Cek stok disimpan: {$changed} bahan dikoreksi, sisanya sesuai."
            : 'Cek stok disimpan: semua bahan sesuai sistem.');
    }

    // ---------- Helpers ----------

    private function requireShift(): Shift
    {
        return $this->shift ?? throw ValidationException::withMessages(['shift' => 'Buka shift dulu sebelum bertransaksi.']);
    }

    private function toast(string $msg, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $msg, type: $type);
    }

    public function render()
    {
        return view('livewire.pos', [
            'closedShift' => $this->closedShiftId ? Shift::with('opener', 'closer')->find($this->closedShiftId) : null,
            'rp' => fn ($v) => ReportService::rp($v),
        ]);
    }
}
