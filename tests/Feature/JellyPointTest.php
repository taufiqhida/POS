<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\ManageCustomers;
use App\Livewire\Pos;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Setting;
use App\Models\StampLog;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class JellyPointTest extends TestCase
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
        Setting::put('stamps_per_reward', '10');
        $this->actingAs($this->kasir)->withSession(['pos_outlet_id' => $this->outlet->id]);
    }

    private function menu(string $name): Menu
    {
        return Menu::where('outlet_id', $this->outlet->id)->where('name', $name)->firstOrFail();
    }

    private function member(array $attrs = []): Customer
    {
        return Customer::create($attrs + ['phone' => '6281234567890', 'name' => 'Budi', 'pin' => '4321']);
    }

    private function row(string $name, int $qty = 1, array $extra = []): array
    {
        return ['menu_id' => $this->menu($name)->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => $qty,
            'promo_id' => null, 'name' => $name] + $extra;
    }

    private function pay(array $cart, ?Customer $c, string $channel = 'offline'): Transaction
    {
        $total = app(PosService::class)->price($this->outlet->id, $cart, 0, $c)['total'];

        return app(PosService::class)->checkout($this->kasir, $this->outlet->openShift(), $cart, 'cash', $channel, $total + 50000, 0, null, $c);
    }

    public function test_phone_numbers_are_normalized(): void
    {
        foreach (['081234567890', '+62 812-3456-7890', '81234567890', '6281234567890'] as $raw) {
            $this->assertSame('6281234567890', Customer::normalizePhone($raw));
        }
        foreach (['', '12345', '0212345678', 'abc'] as $bad) {
            $this->assertNull(Customer::normalizePhone($bad));
        }
    }

    public function test_default_stamps_by_menu_type(): void
    {
        $this->assertSame(3, Menu::defaultStamps('Waffle', 17000));
        $this->assertSame(2, Menu::defaultStamps('Jelly Series', 12000));
        $this->assertSame(1, Menu::defaultStamps('Es Teh', 5000));
        $this->assertSame(3, $this->menu('Waffle Pandan')->stamps);
    }

    public function test_checkout_adds_stamps_per_item_and_logs_them(): void
    {
        $c = $this->member();
        $waffle = $this->menu('Waffle Pandan')->stamps;
        $trx = $this->pay([$this->row('Waffle Pandan', 2)], $c);

        $this->assertSame($waffle * 2, $trx->stamps_earned);
        $c->refresh();
        $this->assertSame($waffle * 2, $c->stamps);
        $this->assertSame(1, $c->visits);
        $this->assertNotNull($c->last_visit_at);
        $this->assertDatabaseHas('stamp_logs', ['customer_id' => $c->id, 'transaction_id' => $trx->id, 'reason' => 'earn']);
    }

    public function test_staff_online_orders_and_no_member_earn_nothing(): void
    {
        $staff = $this->member(['is_staff' => true]);
        $this->assertSame(0, $this->pay([$this->row('Waffle Pandan')], $staff)->stamps_earned);

        $c = $this->member(['phone' => '6289999999999']);
        $this->assertSame(0, $this->pay([$this->row('Waffle Pandan')], $c, 'gofood')->stamps_earned);
        $this->assertSame(0, $c->fresh()->stamps);

        $this->assertNull($this->pay([$this->row('Waffle Pandan')], null)->customer_id);
    }

    public function test_redeem_reward_costs_stamps_and_item_is_free(): void
    {
        $tea = $this->menu('Jasmine Tea');
        $tea->update(['is_reward' => true]);
        $c = $this->member(['stamps' => 12]);
        $cart = [$this->row('Waffle Pandan'), $this->row('Jasmine Tea', 1, ['reward' => true])];

        $priced = app(PosService::class)->price($this->outlet->id, $cart, 0, $c);
        $this->assertSame($this->menu('Waffle Pandan')->base_price, $priced['total']);   // minuman hadiah Rp0

        $trx = $this->pay($cart, $c);
        $this->assertSame(10, $trx->stamps_used);
        $this->assertSame(12 - 10 + $trx->stamps_earned, $c->fresh()->stamps);
        $this->assertTrue((bool) $trx->items()->where('menu_name', 'Jasmine Tea')->first()->is_reward);
        $this->get(route('receipt', $trx))->assertOk()->assertSee('Hadiah Jelly Point')->assertSee('Member: Budi');
    }

    public function test_reward_is_rejected_when_not_allowed(): void
    {
        $this->menu('Jasmine Tea')->update(['is_reward' => true]);
        $cart = [$this->row('Jasmine Tea', 1, ['reward' => true])];

        $rejected = 0;
        foreach ([null, $this->member(['stamps' => 9]), $this->member(['phone' => '6281111111111', 'stamps' => 50, 'is_staff' => true])] as $c) {
            try {
                app(PosService::class)->price($this->outlet->id, $cart, 0, $c);
                $this->fail('Hadiah harus ditolak');
            } catch (ValidationException) {
                $rejected++;
            }
        }
        $this->assertSame(3, $rejected);

        // Menu yang tidak ditandai hadiah, atau lebih dari satu hadiah, juga ditolak.
        $rich = $this->member(['phone' => '6282222222222', 'stamps' => 99]);
        foreach ([[$this->row('Mango Tea', 1, ['reward' => true])],
            [$this->row('Jasmine Tea', 1, ['reward' => true]), $this->row('Jasmine Tea', 1, ['reward' => true])]] as $bad) {
            try {
                app(PosService::class)->price($this->outlet->id, $bad, 0, $rich);
                $this->fail('Hadiah harus ditolak');
            } catch (ValidationException) {
                $rejected++;
            }
        }
    }

    public function test_void_returns_redeemed_stamps_and_removes_earned_ones(): void
    {
        $this->menu('Jasmine Tea')->update(['is_reward' => true]);
        $c = $this->member(['stamps' => 10]);
        $trx = $this->pay([$this->row('Waffle Pandan'), $this->row('Jasmine Tea', 1, ['reward' => true])], $c);
        $this->assertSame($trx->stamps_earned, $c->fresh()->stamps);

        $owner = User::where('role', 'owner')->first();
        app(PosService::class)->requestCancel($trx, $this->kasir, 'void', 'salah');
        $this->assertSame($trx->stamps_earned, $c->fresh()->stamps);           // belum disetujui: belum berubah
        app(PosService::class)->approveCancel($trx->fresh(), $owner);

        $this->assertSame(10, $c->fresh()->stamps);
        $this->assertDatabaseHas('stamp_logs', ['transaction_id' => $trx->id, 'reason' => 'reverse']);
    }

    public function test_stamps_expire_after_six_months_without_visit(): void
    {
        $c = $this->member(['stamps' => 8, 'last_visit_at' => now()->subMonths(7)]);
        $this->assertSame(0, $c->expireIfStale()->stamps);
        $this->assertDatabaseHas('stamp_logs', ['customer_id' => $c->id, 'reason' => 'expire', 'change' => -8]);

        $fresh = $this->member(['phone' => '6283333333333', 'stamps' => 8, 'last_visit_at' => now()->subMonths(2)]);
        $this->assertSame(8, $fresh->expireIfStale()->stamps);
    }

    public function test_pin_locks_after_five_failures_until_reset(): void
    {
        $c = $this->member();
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($c->checkPin('0000'));
        }
        $this->assertFalse($c->fresh()->checkPin('4321'));      // sudah terkunci, PIN benar pun ditolak
        $this->assertTrue($c->fresh()->isLocked());

        $c->update(['pin' => '1111', 'pin_failed' => 0]);
        $this->assertTrue($c->fresh()->checkPin('1111'));
    }

    public function test_cashier_flow_register_earn_and_redeem(): void
    {
        $this->menu('Jasmine Tea')->update(['is_reward' => true]);
        $waffle = $this->menu('Waffle Pandan');

        $lw = Livewire::test(Pos::class)->set('category', '')
            ->call('pick', $waffle->id)->call('addToCart')->call('openPay')
            ->set('memberPhone', '0812-3456-7890')->call('findMember')->assertSet('memberRegister', true)
            ->set('memberName', 'Siti')->set('memberPin', '12')->call('registerMember')->assertHasErrors('memberPin')
            ->set('memberPin', '1234')->call('registerMember')->assertHasNoErrors();

        $c = Customer::where('phone', '6281234567890')->first();
        $this->assertSame('Siti', $c->name);
        $this->assertTrue($c->can_contact);
        $this->assertNotSame('1234', $c->pin_hash);

        $lw->set('paid', 100000)->call('checkout')->assertHasNoErrors()->assertSee('Pembayaran berhasil')->assertSee('Siti');
        $this->assertSame($waffle->stamps, $c->fresh()->stamps);

        // Transaksi berikutnya: cap cukup → tukar hadiah dengan PIN pelanggan.
        $c->update(['stamps' => 10]);
        $lw->call('newOrder')->call('pick', $waffle->id)->call('addToCart')->call('openPay')
            ->set('memberPhone', '081234567890')->call('findMember')->assertSet('memberRegister', false)
            ->call('openReward')->assertSet('rewardOpen', true)
            ->set('rewardPin', '9999')->call('redeemReward')->assertHasErrors('rewardPin')
            ->set('rewardPin', '1234')->call('redeemReward')->assertHasNoErrors()->assertSet('rewardOpen', false);

        $this->assertTrue(collect($lw->get('cart'))->contains(fn ($l) => ! empty($l['reward'])));
        $lw->set('paid', 100000)->call('checkout')->assertHasNoErrors();

        $this->assertSame(2, Transaction::count());
        $trx = Transaction::where('customer_id', $c->id)->orderByDesc('stamps_used')->first();
        $this->assertSame(10, $trx->stamps_used);
        $this->assertSame($waffle->base_price, $trx->total);
        $this->assertSame($waffle->stamps, $c->fresh()->stamps);   // 10 - 10 + cap waffle
    }

    public function test_changing_member_removes_the_reward_line(): void
    {
        $this->menu('Jasmine Tea')->update(['is_reward' => true]);
        $this->member(['stamps' => 10]);

        $lw = Livewire::test(Pos::class)->set('category', '')
            ->call('pick', $this->menu('Waffle Pandan')->id)->call('addToCart')->call('openPay')
            ->set('memberPhone', '081234567890')->call('findMember')
            ->call('openReward')->set('rewardPin', '4321')->call('redeemReward');
        $this->assertCount(2, $lw->get('cart'));

        $lw->call('clearMember');
        $this->assertCount(1, $lw->get('cart'));
    }

    public function test_cashier_cannot_forge_member_id(): void
    {
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(Pos::class)->set('customerId', 'abc');
    }

    public function test_owner_manages_members_in_admin(): void
    {
        $this->actingAs(User::where('role', 'owner')->first());
        $c = $this->member(['pin_failed' => 5, 'last_visit_at' => now()->subDays(40), 'can_contact' => true]);

        Livewire::test(ManageCustomers::class)
            ->assertCanSeeTableRecords([$c])
            ->filterTable('follow_up')->assertCanSeeTableRecords([$c])
            ->callTableAction('resetPin', $c, ['pin' => '5555'])->assertHasNoTableActionErrors()
            ->callTableAction('contacted', $c);

        $this->assertSame(0, $c->fresh()->pin_failed);
        $this->assertTrue($c->fresh()->checkPin('5555'));
        $this->assertNotNull($c->fresh()->last_contacted_at);
        $this->assertStringContainsString('https://wa.me/6281234567890?text=', $c->whatsappUrl());
        $this->get('/admin/customers')->assertOk();
        $this->assertSame(0, StampLog::count());
    }
}
