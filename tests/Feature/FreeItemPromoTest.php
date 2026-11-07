<?php

namespace Tests\Feature;

use App\Filament\Resources\PromoResource\Pages\ManagePromos;
use App\Livewire\Pos;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Promo;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FreeItemPromoTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $kasir;

    private Promo $promo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->outlet = Outlet::where('name', 'Grafika')->first();
        $this->kasir = User::where('name', 'Eka')->first();
        app(ShiftService::class)->open($this->kasir, $this->outlet, 0);
        $this->promo = Promo::create([
            'name' => 'Beli 2 Waffle gratis Es Teh', 'type' => 'free_item', 'buy_qty' => 2,
            'trigger_categories' => ['Waffle'], 'reward_categories' => ['Es Teh · Teh Series'], 'reward_qty' => 1,
        ]);
        $this->actingAs($this->kasir)->withSession(['pos_outlet_id' => $this->outlet->id]);
    }

    private function menu(string $name): Menu
    {
        return Menu::where('outlet_id', $this->outlet->id)->where('name', $name)->firstOrFail();
    }

    private function row(string $name, int $qty = 1, bool $free = false): array
    {
        return ['menu_id' => $this->menu($name)->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => $qty,
            'promo_id' => $free ? $this->promo->id : null, 'name' => $name];
    }

    private function price(array $cart): array
    {
        return app(PosService::class)->price($this->outlet->id, $cart);
    }

    public function test_two_waffles_earn_one_free_tea(): void
    {
        $r = $this->price([$this->row('Waffle Pandan', 2), $this->row('Jasmine Tea', 1, true)]);

        $this->assertSame(34000, $r['total']);                 // hanya 2 waffle yang dibayar
        $this->assertSame(0, $r['lines'][1]['lineTotal']);
        $this->assertStringContainsString('Beli 2 Waffle gratis Es Teh', $r['discount_label']);
    }

    public function test_cannot_claim_more_free_items_than_earned(): void
    {
        $this->expectException(ValidationException::class);
        $this->price([$this->row('Waffle Pandan', 1), $this->row('Jasmine Tea', 1, true)]);      // baru 1 waffle
    }

    public function test_multiples_stack_but_stop_at_the_limit(): void
    {
        $ok = $this->price([$this->row('Waffle Pandan', 4), $this->row('Jasmine Tea', 2, true)]);
        $this->assertSame(68000, $ok['total']);

        $this->expectException(ValidationException::class);
        $this->price([$this->row('Waffle Pandan', 4), $this->row('Jasmine Tea', 3, true)]);
    }

    public function test_free_item_must_match_the_reward(): void
    {
        $this->expectException(ValidationException::class);
        $this->price([$this->row('Waffle Pandan', 2), $this->row('Coklat Lava', 1, true)]);       // bukan Es Teh
    }

    public function test_free_items_do_not_trigger_promos_themselves(): void
    {
        $this->expectException(ValidationException::class);
        // 2 Es Teh biasa + 1 gratis: Es Teh bukan pemicu
        $this->price([$this->row('Mango Tea', 2), $this->row('Jasmine Tea', 1, true)]);
    }

    public function test_inactive_or_expired_promo_gives_nothing(): void
    {
        $this->promo->update(['ends_at' => now()->subDay()]);
        $this->expectException(ValidationException::class);
        $this->price([$this->row('Waffle Pandan', 2), $this->row('Jasmine Tea', 1, true)]);
    }

    public function test_percent_promo_ignores_free_lines(): void
    {
        Promo::create(['name' => 'Hemat 10%', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 30000]);
        $r = $this->price([$this->row('Waffle Pandan', 2), $this->row('Jasmine Tea', 1, true)]);
        $this->assertSame(3400, $r['discount']);               // 10% dari 34.000, bukan dari harga gratisan
        $this->assertSame(30600, $r['total']);
    }

    public function test_checkout_records_free_item_and_still_deducts_stock(): void
    {
        $cup = Ingredient::where('outlet_id', $this->outlet->id)->where('name', 'Cup Es Teh')->first();
        $before = $cup->stock_current;
        $shift = $this->outlet->openShift();

        $trx = app(PosService::class)->checkout($this->kasir, $shift,
            [$this->row('Waffle Pandan', 2), $this->row('Jasmine Tea', 1, true)], 'cash', 'offline', 40000);

        $this->assertSame(34000, $trx->total);
        $this->assertSame(6000, $trx->change_amount);
        $free = $trx->items()->where('menu_name', 'Jasmine Tea')->first();
        $this->assertSame('Beli 2 Waffle gratis Es Teh', $free->promo_name);
        $this->assertSame(0, $free->subtotal);
        $this->assertEquals($before - 1, $cup->fresh()->stock_current);

        $this->get(route('receipt', $trx))->assertOk()->assertSee('GRATIS — Beli 2 Waffle gratis Es Teh');

        // Void disetujui: stok gratisan ikut kembali.
        $owner = User::where('role', 'owner')->first();
        app(PosService::class)->requestCancel($trx, $this->kasir, 'void', 'salah');
        app(PosService::class)->approveCancel($trx->fresh(), $owner);
        $this->assertEquals($before, $cup->fresh()->stock_current);
    }

    public function test_cashier_flow_claim_then_losing_the_requirement_removes_the_gift(): void
    {
        $waffle = $this->menu('Waffle Pandan');
        $tea = $this->menu('Jasmine Tea');

        $lw = Livewire::test(Pos::class)->set('category', '')
            ->call('pick', $waffle->id)->call('addToCart')
            ->assertSeeHtml('Tambah <b>1</b> lagi')                                // petunjuk: kurang 1 waffle
            ->call('pick', $waffle->id)->call('addToCart')
            ->assertSee('Dapat 1 gratisan')
            ->call('openFree', $this->promo->id)->assertSee('Jasmine Tea')
            ->call('claimFree', $tea->id)->assertSet('freePromoId', null);

        $cart = $lw->get('cart');
        $this->assertCount(2, $cart);
        $freeKey = collect($cart)->search(fn ($l) => $l['promo_id'] !== null);
        $waffleKey = collect($cart)->search(fn ($l) => $l['promo_id'] === null);

        $lw->assertSee('GRATIS · Beli 2 Waffle gratis Es Teh');
        $lw->call('qty', $freeKey, 1);                                   // jumlah gratisan tidak bisa dinaikkan
        $this->assertSame(1, $lw->get('cart')[$freeKey]['qty']);

        $lw->call('qty', $waffleKey, -1);                                // waffle jadi 1 → gratisan dibatalkan otomatis
        $this->assertCount(1, $lw->get('cart'));
        $lw->assertDispatched('toast');
    }

    public function test_cashier_checkout_with_free_item(): void
    {
        $lw = Livewire::test(Pos::class)->set('category', '')
            ->call('pick', $this->menu('Waffle Pandan')->id)->call('addToCart')
            ->call('pick', $this->menu('Waffle Pandan')->id)->call('addToCart')
            ->call('openFree', $this->promo->id)->call('claimFree', $this->menu('Mango Tea')->id)
            ->call('openPay')->set('paid', 34000)->set('noMemberOk', true)->call('checkout')
            ->assertHasNoErrors()->assertSee('Pembayaran berhasil');

        $this->assertSame(34000, Transaction::first()->total);
    }

    public function test_owner_creates_free_item_promo_in_admin(): void
    {
        $this->actingAs(User::where('role', 'owner')->first());

        Livewire::test(ManagePromos::class)
            ->callAction('create', ['name' => 'Beli 3 Kebab gratis Teh', 'type' => 'free_item', 'buy_qty' => 3,
                'trigger_categories' => ['Kebab'], 'reward_categories' => ['Es Teh · Teh Series'], 'reward_qty' => 1, 'is_active' => true])
            ->assertHasNoActionErrors();
        $p = Promo::where('name', 'Beli 3 Kebab gratis Teh')->first();
        $this->assertSame('Beli 3 Kebab → gratis 1 Es Teh · Teh Series', $p->describe());
        $this->assertSame(0, $p->value);

        // Tanpa pemicu / hadiah harus ditolak.
        Livewire::test(ManagePromos::class)
            ->callAction('create', ['name' => 'Kosong', 'type' => 'free_item', 'buy_qty' => 2, 'reward_qty' => 1])
            ->assertHasActionErrors(['trigger_categories', 'reward_categories']);

        $this->get('/admin/promos')->assertOk()->assertSee('Beli 3 Kebab');
    }
}
