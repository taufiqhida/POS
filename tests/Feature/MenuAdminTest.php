<?php

namespace Tests\Feature;

use App\Filament\Resources\MenuResource\Pages\ManageMenus;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\User;
use App\Services\PosService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('role', 'owner')->first());
    }

    public function test_copy_menu_to_other_outlet(): void
    {
        $tembalang = Outlet::where('name', 'Tembalang')->first();
        $grafika = Outlet::where('name', 'Grafika')->first();
        $menu = Menu::where('outlet_id', $tembalang->id)->where('name', 'Coklat Lava')->first();
        Menu::where('outlet_id', $grafika->id)->where('name', 'Coklat Lava')->delete();

        Livewire::test(ManageMenus::class)
            ->callTableAction('copy', $menu, ['outlet_id' => $grafika->id])
            ->assertHasNoTableActionErrors();

        $copy = Menu::where('outlet_id', $grafika->id)->where('name', 'Coklat Lava')->first();
        $this->assertNotNull($copy);
        $this->assertSame(2, $copy->variants()->count());
        $this->assertSame($menu->recipes()->count(), $copy->recipes()->count());
    }

    public function test_delete_menu_without_sales(): void
    {
        $menu = Menu::where('name', 'Jasmine Tea')->first();
        Livewire::test(ManageMenus::class)->callTableAction('delete', $menu)->assertHasNoTableActionErrors();
        $this->assertNull(Menu::find($menu->id));
    }

    public function test_delete_menu_that_has_sales(): void
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $shift = app(ShiftService::class)->open($kasir, $outlet, 0);
        $menu = Menu::where('outlet_id', $outlet->id)->where('name', 'Jasmine Tea')->first();
        app(PosService::class)->checkout($kasir, $shift, [['menu_id' => $menu->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => 1]], 'qris', 'offline', 0);

        Livewire::test(ManageMenus::class)->callTableAction('delete', $menu)->assertHasNoTableActionErrors();

        $this->assertNull(Menu::find($menu->id));                       // hilang dari kasir & admin
        $this->assertNotNull(Menu::withTrashed()->find($menu->id));      // tapi tetap ada untuk riwayat
        $this->assertDatabaseHas('transaction_items', ['menu_id' => $menu->id, 'menu_name' => 'Jasmine Tea']);
    }
}
