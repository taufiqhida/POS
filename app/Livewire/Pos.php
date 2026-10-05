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
        if (empty($this->cart)) {
            return ['lines' => [], 'subtotal' => 0, 'discount' => 0, 'discount_label' => null, 'total' => 0];
        }

        return app(PosService::class)->price($this->outletId, array_values($this->cart), $this->manualDiscount);
    }

    // ---------- Shift ----------

    public function openShift(ShiftService $svc): void
    {
        $this->validate(['openingCash' => 'required|integer|min:0'], [], ['openingCash' => 'modal awal']);
        $svc->open(Auth::user(), $this->outlet, $this->openingCash);
        $this->openingCash = null;
        $this->closedShiftId = null;
        unset($this->shift);
        $this->toast('Shift dibuka. Selamat bekerja! 🧋');
    }

    public function addExpense(): void
    {
        $this->validate(['expenseDesc' => 'required|string|max:200', 'expenseAmount' => 'required|integer|min:1'], [], ['expenseDesc' => 'keterangan', 'expenseAmount' => 'jumlah']);
        $this->requireShift()->expenses()->create(['description' => $this->expenseDesc, 'amount' => $this->expenseAmount, 'created_by' => Auth::id()]);
        AuditLog::record('expense', Auth::id(), 'shift', $this->shift->id, "{$this->expenseDesc}: Rp".number_format($this->expenseAmount, 0, ',', '.'));
        $this->reset('expenseDesc', 'expenseAmount');
        $this->toast('Pengeluaran dicatat.');
    }

    public function closeShift(ShiftService $svc): void
    {
        $this->validate(['actualCash' => 'required|integer|min:0'], [], ['actualCash' => 'cash fisik']);
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
    }

    public function removeLine(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function clearCart(): void
    {
        $this->reset('cart', 'manualDiscount', 'approverId', 'paid', 'showPay');
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
                $this->validate(['pinValue' => 'required|integer|min:0'], [], ['pinValue' => 'harga baru']);
                $this->cart[$this->pinContext['key']]['price_override'] = $this->pinValue;
                $this->cart[$this->pinContext['key']]['sig'] .= '|o';
                $this->approverId = $approver->id;
                break;
            case 'discount':
                $this->validate(['pinValue' => 'required|integer|min:0'], [], ['pinValue' => 'diskon']);
                $this->manualDiscount = $this->pinValue;
                $this->approverId = $approver->id;
                break;
            case 'void':
            case 'refund':
                $this->validate(['pinReason' => 'required|string|max:200'], [], ['pinReason' => 'alasan']);
                $trx = Transaction::where('outlet_id', $this->outletId)->findOrFail($this->pinContext['id']);
                $pos->cancel($trx, Auth::user(), $approver, $this->pinAction, $this->pinReason);
                $this->toast(($this->pinAction === 'void' ? 'Void' : 'Refund')." {$trx->number} berhasil.");
                break;
        }
        $this->pinAction = null;
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
        return Transaction::with(['cashier', 'items'])->where('outlet_id', $this->outletId)
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
        $rows = array_filter($this->physical, fn ($v) => $v !== '' && $v !== null);
        if (! $rows) {
            $this->toast('Isi minimal satu hitungan fisik.', 'error');

            return;
        }
        DB::transaction(function () use ($rows) {
            foreach ($rows as $id => $qty) {
                $ing = Ingredient::where('outlet_id', $this->outletId)->find($id);
                if (! $ing || ! is_numeric($qty)) {
                    continue;
                }
                $diff = (float) $qty - $ing->stock_current;
                StockCount::create([
                    'outlet_id' => $this->outletId, 'ingredient_id' => $id, 'user_id' => Auth::id(),
                    'system_qty' => $ing->stock_current, 'physical_qty' => $qty, 'difference' => $diff,
                ]);
                if ($diff != 0) {
                    StockMovement::create([
                        'outlet_id' => $this->outletId, 'ingredient_id' => $id, 'qty_change' => $diff,
                        'reason' => $diff < 0 ? 'waste' : 'adjustment', 'user_id' => Auth::id(), 'note' => 'Cek stok fisik',
                    ]);
                    $ing->update(['stock_current' => $qty]);
                }
            }
        });
        $this->physical = [];
        unset($this->ingredients, $this->usageHistory);
        $this->toast('Hitungan stok fisik disimpan.');
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
