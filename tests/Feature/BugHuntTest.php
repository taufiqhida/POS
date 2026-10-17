<?php

namespace Tests\Feature;

use App\Livewire\Pos;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Topping;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Skenario "nakal" — mencari bug sebelum ketemu di outlet. */
class BugHuntTest extends TestCase
{
    use RefreshDatabase;

    private Outlet $outlet;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->outlet = Outlet::where('name', 'Tembalang')->first();
        $this->kasir = User::where('name', 'Elsya')->first();
        app(ShiftService::class)->open($this->kasir, $this->outlet, 100000);
        $this->actingAs($this->kasir)->withSession(['pos_outlet_id' => $this->outlet->id]);
    }

    private function menu(string $name): Menu
    {
        return Menu::where('outlet_id', $this->outlet->id)->where('name', $name)->firstOrFail();
    }

    private function pos()
    {
        return Livewire::test(Pos::class)->set('category', '');
    }

    public function test_menu_disabled_while_in_cart(): void
    {
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)->call('addToCart');
        $this->menu('Jasmine Tea')->update(['is_active' => false]);
        $lw->call('qty', array_key_first($lw->get('cart')), 1)->assertOk()->assertSet('cart', [])->assertDispatched('toast');
    }

    public function test_menu_deleted_while_in_cart(): void
    {
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)->call('addToCart');
        $this->menu('Jasmine Tea')->delete();
        $lw->call('openPay')->assertOk()->assertSet('cart', [])->assertDispatched('toast');
    }

    public function test_manual_discount_does_not_leak_to_next_customer(): void
    {
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)->call('addToCart')
            ->call('askPin', 'discount')->set('pinValue', 2000)->set('pin', '5678')->call('confirmPin');
        $key = array_key_first($lw->get('cart'));
        $lw->call('removeLine', $key)                                   // item dihapus satu-satu, bukan "Batal"
            ->call('pick', $this->menu('Milk Tea')->id)->call('addToCart');
        $this->assertSame(0, $lw->get('manualDiscount'), 'Diskon manual terbawa ke pesanan berikutnya');
    }

    public function test_huge_numbers_do_not_crash(): void
    {
        $this->pos()->set('tab', 'shift')->set('expenseDesc', 'Es')->set('expenseAmount', 99999999999)->call('addExpense')->assertHasErrors('expenseAmount');
        $this->pos()->set('tab', 'shift')->set('actualCash', 99999999999)->call('confirmCloseShift')->assertHasErrors('actualCash');
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)->call('addToCart')
            ->call('openPay')->set('paid', 99999999999)->call('checkout');
        $lw->assertHasErrors('paid');
    }

    public function test_selling_when_stock_runs_out(): void
    {
        $cup = Ingredient::where('outlet_id', $this->outlet->id)->where('name', 'Cup Es Teh')->first();
        $cup->update(['stock_current' => 0]);
        $shift = $this->outlet->openShift();
        app(PosService::class)->checkout($this->kasir, $shift,
            [['menu_id' => $this->menu('Jasmine Tea')->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => 1]], 'qris', 'offline', 0);
        $this->assertEquals(-1, $cup->fresh()->stock_current); // tetap bisa dijual, stok jadi minus

        // Kasir melihat tanda "Stok habis" di menu yang memakai bahan itu, menu lain tidak.
        $this->pos()->assertSee('Stok habis');
        $out = Livewire::test(Pos::class)->instance()->outOfStock;
        $this->assertArrayHasKey($this->menu('Jasmine Tea')->id, $out);
        $this->assertArrayNotHasKey($this->menu('Coklat Lava')->id, $out);

        // Menu dengan ukuran: Cup Medium habis → Coklat Lava masih bisa ukuran Reguler, jadi tidak ditandai habis.
        Ingredient::where('outlet_id', $this->outlet->id)->where('name', 'Cup Medium')->update(['stock_current' => 0]);
        $this->assertArrayNotHasKey($this->menu('Coklat Lava')->id, Livewire::test(Pos::class)->instance()->outOfStock);
    }

    public function test_topping_from_other_group_is_ignored_in_price(): void
    {
        $boba = Topping::where('name', 'Boba')->first();
        $extra = Topping::where('name', 'Extra Telur')->first();
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)
            ->call('toggleTopping', $boba->id)->call('toggleTopping', $extra->id)->call('addToCart');
        $this->assertSame(8000, $lw->get('priced')['total'] ?? app(PosService::class)->price($this->outlet->id, array_values($lw->get('cart')))['total']);
    }

    public function test_cart_name_shows_topping_from_other_group(): void
    {
        $extra = Topping::where('name', 'Extra Telur')->first();
        $lw = $this->pos()->call('pick', $this->menu('Jasmine Tea')->id)->call('toggleTopping', $extra->id)->call('addToCart');
        $this->assertStringNotContainsString('Extra Telur', array_values($lw->get('cart'))[0]['topping_names']);
    }

    public function test_stock_check_rejects_letters_and_negative(): void
    {
        $boba = Ingredient::where('outlet_id', $this->outlet->id)->where('name', 'Boba')->first();
        $this->pos()->set('tab', 'stok')->set("physical.{$boba->id}", -5)->call('saveStockCount');
        $this->assertNotEquals(-5, $boba->fresh()->stock_current);
    }

    public function test_open_shift_twice_from_two_tabs(): void
    {
        $this->outlet->openShift()->update(['status' => 'closed']);
        $a = $this->pos()->set('openingCash', 1000);
        $b = $this->pos()->set('openingCash', 2000);
        $a->call('openShift');
        $b->call('openShift')->assertOk();
        $this->assertSame(1, $this->outlet->shifts()->where('status', 'open')->count());
    }

    public function test_close_shift_twice_from_two_tabs(): void
    {
        $a = $this->pos()->set('tab', 'shift')->set('actualCash', 100000)->call('confirmCloseShift');
        $b = $this->pos()->set('tab', 'shift')->set('actualCash', 100000)->call('confirmCloseShift');
        $a->call('closeShift');
        $b->call('closeShift')->assertOk();
    }

    public function test_admin_create_menu_without_sizes_and_sell_it(): void
    {
        $owner = User::where('role', 'owner')->first();
        $this->actingAs($owner);
        Livewire::test(\App\Filament\Resources\MenuResource\Pages\ManageMenus::class)
            ->callAction('create', ['outlet_id' => $this->outlet->id, 'name' => 'Menu Baru', 'category' => 'Coba',
                'base_price' => 7000, 'topping_group' => 'minuman', 'free_toppings' => 0, 'is_active' => true])
            ->assertHasNoActionErrors();
        $menu = $this->menu('Menu Baru');

        $this->actingAs($this->kasir);
        $this->pos()->call('pick', $menu->id)->call('addToCart')->call('openPay')->set('paid', 7000)->call('checkout')
            ->assertHasNoErrors()->assertSee('Pembayaran berhasil');
    }
}
