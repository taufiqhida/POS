<?php

namespace Tests\Feature;

use App\Livewire\Pos;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Promo;
use App\Models\Shift;
use App\Models\Topping;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PosFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function setupShift(int $modal = 200000): array
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $shift = app(ShiftService::class)->open($kasir, $outlet, $modal);

        return [$outlet, $kasir, $shift];
    }

    private function line(Outlet $outlet, string $menu, array $toppings = [], int $qty = 1, string $size = 'Reguler'): array
    {
        $m = Menu::with('variants')->where('outlet_id', $outlet->id)->where('name', $menu)->firstOrFail();

        return [
            'menu_id' => $m->id,
            'variant_id' => $m->variants->firstWhere('size', $size)?->id,
            'topping_ids' => Topping::whereIn('name', $toppings)->pluck('id')->all(),
            'qty' => $qty,
        ];
    }

    private function stock(Outlet $outlet, string $name): float
    {
        return Ingredient::where('outlet_id', $outlet->id)->where('name', $name)->firstOrFail()->stock_current;
    }

    public function test_pin_login_and_open_shift_first_win(): void
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();

        $this->post('/kasir/login', ['outlet_id' => $outlet->id, 'user_id' => $kasir->id, 'pin' => '9999'])->assertSessionHasErrors('pin');
        $this->post('/kasir/login', ['outlet_id' => $outlet->id, 'user_id' => $kasir->id, 'pin' => '2222'])->assertRedirect('/kasir');
        $this->get('/kasir')->assertOk()->assertSee('Buka shift');

        Livewire::test(Pos::class)->set('openingCash', 200000)->call('openShift')->assertSee('Belum ada pesanan');
        $this->assertSame(200000, Shift::where('status', 'open')->first()->opening_cash);
    }

    public function test_kasir_cannot_login_to_other_outlet(): void
    {
        $grafika = Outlet::where('name', 'Grafika')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $this->post('/kasir/login', ['outlet_id' => $grafika->id, 'user_id' => $kasir->id, 'pin' => '2222'])->assertSessionHasErrors('outlet_id');
    }

    public function test_menu_seeder_matches_posters_and_is_rerunnable(): void
    {
        $tembalang = Outlet::where('name', 'Tembalang')->first();
        $grafika = Outlet::where('name', 'Grafika')->first();
        // 48 Jelly Potter (termasuk Mocca Squash) + 25 Es Teh JelPot; Grafika + 8 waffle + 6 croffle + 18 kebab + 10 burger + 28 maryam
        $this->assertSame(73, $tembalang->menus()->count());
        $this->assertSame(73 + 70, $grafika->menus()->count());
        $this->assertSame(0, $tembalang->menus()->whereIn('category', ['Waffle', 'Croffle', 'Kebab', 'Burger', 'Maryam'])->count());
        $this->assertSame(17, Menu::where('outlet_id', $tembalang->id)->whereHas('variants', fn ($q) => $q->where('size', 'Medium'))->count());

        $this->seed(MenuSeeder::class);
        $this->assertSame(73, $tembalang->menus()->count());
        $this->assertSame(143, $grafika->menus()->count());
        $this->assertSame(2, Menu::where('outlet_id', $tembalang->id)->where('name', 'Coklat Lava')->first()->variants()->count());

        $this->assertSame(13000, Menu::where('name', 'Coklat Lava')->first()->base_price);
        $this->assertSame(16000, Menu::where('name', 'Mango Mix Yakult')->first()->base_price);
        $this->assertSame(5000, Menu::where('name', 'Jasmine Tea')->first()->base_price);
        $this->assertSame(6000, Menu::where('name', 'Yuzu')->first()->base_price);
        $this->assertSame(8000, Menu::where('name', 'Klepon Mania')->first()->base_price);
        $this->assertSame(['Boba', 'Choco Chips', 'Jelly Cendol', 'Rodeo'], Topping::where('group', 'minuman')->orderBy('name')->pluck('name')->all());
        $this->assertSame(13, Topping::where('group', 'waffle')->count());
        $this->assertSame(24000, Menu::where('name', 'Beef Kebab Besar Special')->first()->base_price);
        $this->assertSame(8000, Menu::where('name', 'Original Croffle')->first()->base_price);
        $this->assertSame(11000, Menu::where('name', 'Crunchy Choco Croffle')->first()->base_price);
        $this->assertSame(['Elsya', 'Mia'], User::where('outlet_id', $tembalang->id)->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Eka', 'Pasya'], User::where('outlet_id', $grafika->id)->orderBy('name')->pluck('name')->all());
        $this->assertSame(15000, Menu::where('name', 'Maryam Special (Coklat, Keju, Selai, Chocochip)')->first()->base_price);
        $this->assertSame(0, Menu::whereDoesntHave('recipes')->count());
    }

    public function test_no_transaction_without_shift(): void
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $shift = app(ShiftService::class)->open($kasir, $outlet, 0);
        app(ShiftService::class)->close($shift, $kasir, 0);

        $this->expectException(ValidationException::class);
        app(PosService::class)->checkout($kasir, $shift->fresh(), [$this->line($outlet, 'Coklat Lava')], 'cash', 'offline', 20000);
    }

    public function test_first_topping_free_and_stock_deducted(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $boba = $this->stock($outlet, 'Boba');
        $cup = $this->stock($outlet, 'Cup Jelly Potter');

        // Coklat Lava 13.000 + Boba (gratis) + Choco Chips 3.000 = 16.000 × 2
        $trx = app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava', ['Boba', 'Choco Chips'], 2)], 'cash', 'offline', 50000);

        $this->assertSame(32000, $trx->total);
        $this->assertSame(18000, $trx->change_amount);
        $this->assertSame($kasir->id, $trx->cashier_id);
        $this->assertEquals($boba - 80, $this->stock($outlet, 'Boba'));
        $this->assertEquals($cup - 2, $this->stock($outlet, 'Cup Jelly Potter'));
        $this->assertSame(1, $trx->items->first()->toppings->where('price_each', 0)->count());
    }

    public function test_es_teh_and_yakult_pricing(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $pos = app(PosService::class);

        // Es Teh tidak termasuk topping gratis: 5.000 + Boba 3.000
        $this->assertSame(8000, $pos->checkout($kasir, $shift, [$this->line($outlet, 'Jasmine Tea', ['Boba'])], 'qris', 'offline', 0)->total);
        // Mix Yakult = 13.000 + 3.000, topping pertama gratis, Yakult terpotong 1 botol
        $yakult = $this->stock($outlet, 'Yakult');
        $this->assertSame(16000, $pos->checkout($kasir, $shift, [$this->line($outlet, 'Mango Mix Yakult', ['Rodeo'])], 'qris', 'offline', 0)->total);
        $this->assertEquals($yakult - 1, $this->stock($outlet, 'Yakult'));
    }

    public function test_medium_size_uses_medium_price_and_cup(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $medium = $this->stock($outlet, 'Cup Medium');
        $reguler = $this->stock($outlet, 'Cup Jelly Potter');

        $trx = app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Grape Squash', ['Boba'], 1, 'Medium')], 'qris', 'offline', 0);

        $this->assertSame(10000, $trx->total); // Medium 10.000, topping pertama gratis
        $this->assertEquals($medium - 1, $this->stock($outlet, 'Cup Medium'));
        $this->assertEquals($reguler, $this->stock($outlet, 'Cup Jelly Potter'));
    }

    public function test_menu_with_sizes_requires_a_size(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $this->expectException(ValidationException::class);
        app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Grape Squash', [], 1, 'Jumbo')], 'qris', 'offline', 0);
    }

    public function test_waffle_addons_and_topping_groups_are_isolated(): void
    {
        $grafika = Outlet::where('name', 'Grafika')->first();
        $kasirGrafika = User::where('name', 'Pasya')->first();
        $shift = app(ShiftService::class)->open($kasirGrafika, $grafika, 0);
        $pos = app(PosService::class);

        // Waffle 17.000 + Isian Keju 4.000 + Olesan Matcha 5.000 + Olesan Cheese 8.000 (tidak ada topping gratis)
        $line = $this->line($grafika, 'Waffle Pandan', ['Isian Keju', 'Olesan Matcha', 'Olesan Cheese']);
        $this->assertSame(34000, $pos->checkout($kasirGrafika, $shift, [$line], 'qris', 'offline', 0)->total);

        // Topping minuman (Boba) tidak bisa dipasang ke kebab — diabaikan oleh sistem.
        $kebab = $this->line($grafika, 'Beef Kebab Kecil', ['Boba', 'Extra Telur']);
        $this->assertSame(15000, $pos->checkout($kasirGrafika, $shift, [$kebab], 'qris', 'offline', 0)->total);
    }

    public function test_promo_applies_automatically(): void
    {
        Promo::create(['name' => 'Hemat 10%', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 50000, 'is_active' => true]);
        [$outlet, $kasir, $shift] = $this->setupShift();
        // 4 × 13.000 = 52.000 → diskon 5.200
        $trx = app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava', [], 4)], 'qris', 'offline', 0);
        $this->assertSame(5200, $trx->discount);
        $this->assertSame(46800, $trx->total);
    }

    public function test_price_override_requires_authorised_pin(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $line = $this->line($outlet, 'Coklat Lava') + ['price_override' => 5000];

        try {
            app(PosService::class)->checkout($kasir, $shift, [$line], 'cash', 'offline', 5000);
            $this->fail('Harus ditolak tanpa PIN');
        } catch (ValidationException) {
        }

        $owner = User::where('role', 'owner')->first();
        $trx = app(PosService::class)->checkout($kasir, $shift, [$line], 'cash', 'offline', 5000, 0, $owner);
        $this->assertSame(5000, $trx->total);
        $this->assertDatabaseHas('audit_logs', ['action' => 'price_change', 'user_id' => $kasir->id, 'approved_by' => $owner->id]);
    }

    public function test_close_shift_shows_cash_difference(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift(1000000);
        $pos = app(PosService::class);
        $pos->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava', [], 20)], 'cash', 'offline', 260000); // 260.000 tunai
        $pos->checkout($kasir, $shift, [$this->line($outlet, 'Milo Choco')], 'qris', 'offline', 0);                 // QRIS tidak masuk laci
        $pos->checkout($kasir, $shift, [$this->line($outlet, 'Jasmine Tea')], 'cash', 'gofood', 0);                // GoFood tunai 5.000
        $shift->expenses()->create(['description' => 'Es batu', 'amount' => 15000, 'created_by' => $kasir->id]);

        $expected = 1000000 + 260000 + 5000 - 15000; // = 1.250.000 (contoh PRD)
        $this->assertSame(1250000, $expected);
        $closed = app(ShiftService::class)->close($shift, $kasir, $expected - 25000);

        $this->assertSame($expected, $closed->expected_cash);
        $this->assertSame(-25000, $closed->cash_difference);
        $this->assertSame('closed', $closed->status);
    }

    public function test_void_needs_manager_pin_and_restores_stock(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $before = $this->stock($outlet, 'Jelly');
        $trx = app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava')], 'cash', 'offline', 13000);
        $this->assertLessThan($before, $this->stock($outlet, 'Jelly'));

        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);
        Livewire::test(Pos::class)
            ->call('askPin', 'void', ['id' => $trx->id])->set('pinReason', 'Salah input')->set('pin', '1111')->call('confirmPin')
            ->assertHasErrors('pin')
            ->set('pin', '5678')->call('confirmPin')->assertHasNoErrors();

        $this->assertSame('void', $trx->fresh()->status);
        $this->assertEquals($before, $this->stock($outlet, 'Jelly'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'void', 'user_id' => $kasir->id]);
    }

    public function test_full_livewire_cart_checkout(): void
    {
        [$outlet, $kasir] = $this->setupShift();
        $menu = Menu::where('outlet_id', $outlet->id)->where('name', 'Taro Flavor')->first();
        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);

        Livewire::test(Pos::class)
            ->call('pick', $menu->id)->assertSee('1 topping gratis')->call('addToCart')
            ->call('pick', $menu->id)->call('addToCart')
            ->assertSet('cart', fn ($c) => count($c) === 1 && array_values($c)[0]['qty'] === 2)
            ->call('openPay')->set('paid', 50000)->call('checkout')
            ->assertHasNoErrors()->assertSee('Pembayaran berhasil');

        $this->assertSame(26000, Transaction::first()->total);
    }

    public function test_best_menu_auto_from_sales_then_manual_override(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        $grafika = Outlet::where('name', 'Grafika')->first();
        $pos = app(PosService::class);
        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);

        // Belum ada penjualan & belum ditandai: tab Best kosong, kasir mulai di "Semua".
        Livewire::test(Pos::class)->assertSet('category', '');

        // Otomatis: urut dari yang paling banyak terjual di outlet ini saja.
        $pos->checkout($kasir, $shift, [$this->line($outlet, 'Jasmine Tea', [], 5)], 'qris', 'offline', 0);
        $pos->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava', [], 2)], 'qris', 'offline', 0);
        [$ids, $mode] = Menu::bestFor($outlet->id);
        $this->assertSame('auto', $mode);
        $this->assertSame(['Jasmine Tea', 'Coklat Lava'], Menu::findMany($ids)->sortBy(fn ($m) => array_search($m->id, $ids))->pluck('name')->values()->all());
        $this->assertSame([], Menu::bestFor($grafika->id)[0]);

        Livewire::test(Pos::class)
            ->assertSet('category', Pos::BEST)
            ->assertSee('Jasmine Tea')->assertDontSee('Matcha Flavor')
            ->set('search', 'Matcha')->assertSee('Matcha Flavor'); // pencarian tetap ke semua menu

        // Manual: begitu owner menandai menu, daftar otomatis diganti.
        Menu::where('outlet_id', $outlet->id)->where('name', 'Taro Flavor')->update(['is_best' => true]);
        [$ids, $mode] = Menu::bestFor($outlet->id);
        $this->assertSame('manual', $mode);
        $this->assertSame(['Taro Flavor'], Menu::findMany($ids)->pluck('name')->all());

        // Seeder ulang tidak menghapus pilihan owner.
        $this->seed(MenuSeeder::class);
        $this->assertTrue(Menu::where('outlet_id', $outlet->id)->where('name', 'Taro Flavor')->value('is_best'));
    }

    public function test_admin_pages_render_for_owner(): void
    {
        $owner = User::where('role', 'owner')->first();
        [$outlet, $kasir, $shift] = $this->setupShift();
        app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava')], 'cash', 'offline', 13000);
        $this->actingAs($owner);
        foreach (['/admin', '/admin/menus', '/admin/toppings', '/admin/promos', '/admin/ingredients', '/admin/transactions',
            '/admin/shifts', '/admin/stock-movements', '/admin/audit-logs', '/admin/users', '/admin/outlets',
            '/admin/reports', '/admin/daily-report', '/admin/settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_menu_edit_form_with_recipes_opens(): void
    {
        $this->actingAs(User::where('role', 'owner')->first());
        $menu = Menu::where('name', 'Coklat Lava')->first();

        Livewire::test(\App\Filament\Resources\MenuResource\Pages\ManageMenus::class)
            ->mountTableAction('edit', $menu)
            ->assertHasNoTableActionErrors()
            ->assertSee('Khusus ukuran')
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
    }

    public function test_kasir_cannot_access_admin_and_manager_cannot_open_settings(): void
    {
        $this->actingAs(User::where('name', 'Elsya')->first())->get('/admin')->assertForbidden();
        $this->actingAs(User::where('role', 'manager')->first())->get('/admin/settings')->assertForbidden();
    }

    public function test_daily_report_message(): void
    {
        [$outlet, $kasir, $shift] = $this->setupShift();
        app(PosService::class)->checkout($kasir, $shift, [$this->line($outlet, 'Coklat Lava')], 'cash', 'offline', 13000);
        $this->artisan('report:daily')->expectsOutputToContain('Tembalang : Rp13.000')->assertSuccessful();
    }
}
