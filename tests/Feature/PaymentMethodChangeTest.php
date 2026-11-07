<?php

namespace Tests\Feature;

use App\Livewire\Pos;
use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentMethodChangeTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->outlet = Outlet::where('name', 'Grafika')->first();
        $this->kasir = User::where('name', 'Eka')->first();
        app(ShiftService::class)->open($this->kasir, $this->outlet, 0);
        $this->actingAs($this->kasir)->withSession(['pos_outlet_id' => $this->outlet->id]);
    }

    private function payCash(): Transaction
    {
        $menu = Menu::where('outlet_id', $this->outlet->id)->where('name', 'Waffle Pandan')->firstOrFail();
        $cart = [['menu_id' => $menu->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => 1, 'promo_id' => null, 'name' => $menu->name]];
        $total = app(PosService::class)->price($this->outlet->id, $cart)['total'];

        return app(PosService::class)->checkout($this->kasir, $this->outlet->openShift(), $cart, 'cash', 'offline', $total + 5000, 0);
    }

    public function test_cashier_fixes_wrong_method_and_it_is_logged(): void
    {
        $trx = $this->payCash();
        $shift = $this->outlet->openShift();
        $this->assertSame($trx->total, $shift->cashSales());

        $lw = Livewire::test(Pos::class)->call('askMethod', $trx->id)->set('newMethod', 'qris')
            ->set('methodReason', '')->call('submitMethodChange')->assertHasErrors('methodReason');
        $this->assertSame('cash', $trx->fresh()->payment_method);

        $lw->set('methodReason', 'pelanggan bayar QRIS, salah pencet')->call('submitMethodChange')->assertHasNoErrors();

        $trx->refresh();
        $this->assertSame('qris', $trx->payment_method);
        $this->assertSame(0, (int) $trx->change_amount);
        $this->assertSame(0, $shift->fresh()->cashSales());

        $log = AuditLog::where('action', 'payment_change')->where('reference_id', $trx->id)->first();
        $this->assertNotNull($log);
        $this->assertSame($this->kasir->id, $log->user_id);
        $this->assertStringContainsString('Tunai → QRIS', $log->detail);
        $this->assertStringContainsString('salah pencet', $log->detail);
        $this->assertNull($log->reviewed_at);

        // Owner melihatnya di menu khusus, lalu menandai sudah dicek.
        $owner = User::where('role', 'owner')->first();
        $this->actingAs($owner);
        $this->assertSame('1', \App\Filament\Resources\PaymentChangeResource::getNavigationBadge());
        Livewire::test(\App\Filament\Resources\PaymentChangeResource\Pages\ManagePaymentChanges::class)
            ->assertCanSeeTableRecords([$log])->callTableAction('review', $log);
        $this->assertSame($owner->id, $log->fresh()->reviewed_by);
        $this->assertNull(\App\Filament\Resources\PaymentChangeResource::getNavigationBadge());
    }

    public function test_cannot_change_after_shift_closed(): void
    {
        $trx = $this->payCash();
        app(ShiftService::class)->close($this->outlet->openShift(), $this->kasir, 0);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PosService::class)->changePaymentMethod($trx->fresh(), $this->kasir, 'qris', 'salah pencet');
    }
}
