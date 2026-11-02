<?php

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Filament\Resources\MenuResource\Pages\ManageMenus;
use App\Filament\Resources\WarehouseItemResource\Pages\ManageWarehouseItems;
use App\Livewire\Pos;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\Outlet;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Services\ShiftService;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class WarehouseCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function owner(): User
    {
        return User::where('role', 'owner')->first();
    }

    private function outlet(string $name = 'Tembalang'): Outlet
    {
        return Outlet::where('name', $name)->first();
    }

    // ---------- Kategori ----------

    public function test_categories_are_created_from_menus_in_order(): void
    {
        $this->assertSame(Menu::distinct()->count('category'), Category::count());
        $this->assertTrue(Category::ordered()->contains('Es Teh · Teh Series'));
    }

    public function test_rename_category_updates_all_menus(): void
    {
        $this->actingAs($this->owner());
        $cat = Category::where('name', 'Chocolate Series')->first();
        $before = Menu::where('category', 'Chocolate Series')->count();

        Livewire::test(ManageCategories::class)
            ->callTableAction('edit', $cat, ['name' => 'Coklat Spesial'])->assertHasNoTableActionErrors();

        $this->assertSame($before, Menu::where('category', 'Coklat Spesial')->count());
        $this->assertSame(0, Menu::where('category', 'Chocolate Series')->count());
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $this->actingAs($this->owner());
        Livewire::test(ManageCategories::class)
            ->callTableAction('edit', Category::where('name', 'Chocolate Series')->first(), ['name' => 'Fruit Squash'])
            ->assertHasTableActionErrors(['name']);
    }

    public function test_cannot_delete_category_that_still_has_menus_but_can_delete_empty_one(): void
    {
        $this->actingAs($this->owner());
        $used = Category::where('name', 'Fruit Squash')->first();
        Livewire::test(ManageCategories::class)->callTableAction('delete', $used);
        $this->assertNotNull(Category::find($used->id));

        $empty = Category::create(['name' => 'Kosong']);
        Livewire::test(ManageCategories::class)->callTableAction('delete', $empty);
        $this->assertNull(Category::find($empty->id));
    }

    public function test_new_category_from_menu_form_and_cashier_tab_order(): void
    {
        $owner = $this->owner();
        $outlet = $this->outlet();
        $this->actingAs($owner);

        // Tambah menu dengan kategori baru langsung dari form menu (kategori otomatis dibuat).
        Category::ensure('Musiman');
        Livewire::test(ManageMenus::class)
            ->callAction('create', ['outlet_id' => $outlet->id, 'name' => 'Es Musim Baru', 'category' => 'Musiman',
                'base_price' => 9000, 'topping_group' => 'minuman', 'free_toppings' => 0, 'is_active' => true])
            ->assertHasNoActionErrors();

        // Owner memindahkan "Musiman" ke urutan paling depan.
        Category::where('name', 'Musiman')->update(['sort_order' => 1]);

        $kasir = User::where('name', 'Elsya')->first();
        app(ShiftService::class)->open($kasir, $outlet, 0);
        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);

        $cats = Livewire::test(Pos::class)->instance()->categories;
        $this->assertSame('Musiman', $cats->first());
        $this->assertNotContains('Waffle', $cats->all()); // kategori outlet lain tidak muncul
    }

    // ---------- Gudang ----------

    public function test_every_outlet_ingredient_has_a_warehouse_card(): void
    {
        $this->assertSame(0, Ingredient::whereNotIn('name', WarehouseItem::pluck('name'))->count());
        $this->assertEquals(0, WarehouseItem::sum('stock_current'));
    }

    public function test_receive_then_transfer_moves_stock_from_warehouse_to_outlet(): void
    {
        $wh = app(WarehouseService::class);
        $outlet = $this->outlet();
        $kasir = User::where('role', 'manager')->first();
        $item = WarehouseItem::where('name', 'Boba')->first();
        $ing = Ingredient::where('outlet_id', $outlet->id)->where('name', 'Boba')->first();
        $before = $ing->stock_current;

        $wh->receive($item, 100, $this->owner(), 'Nota 123');
        $wh->transfer($item, $outlet, 30, $kasir, 'minggu ini');

        $this->assertEquals(70, $item->fresh()->stock_current);               // gudang berkurang
        $this->assertEquals($before + 30, $ing->fresh()->stock_current);      // outlet bertambah
        $this->assertDatabaseHas('warehouse_movements', ['warehouse_item_id' => $item->id, 'reason' => 'transfer',
            'qty_change' => -30, 'outlet_id' => $outlet->id, 'user_id' => $kasir->id]);
        $this->assertDatabaseHas('stock_movements', ['ingredient_id' => $ing->id, 'reason' => 'transfer', 'qty_change' => 30]);
    }

    public function test_cannot_take_more_than_warehouse_stock(): void
    {
        $item = WarehouseItem::where('name', 'Boba')->first();
        app(WarehouseService::class)->receive($item, 10, $this->owner());
        $ing = Ingredient::where('outlet_id', $this->outlet()->id)->where('name', 'Boba')->first();
        $before = $ing->stock_current;

        try {
            app(WarehouseService::class)->transfer($item, $this->outlet(), 11, $this->owner());
            $this->fail('Harus ditolak');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('hanya 10', $e->validator->errors()->first());
        }
        $this->assertEquals(10, $item->fresh()->stock_current);
        $this->assertEquals($before, $ing->fresh()->stock_current);
    }

    public function test_invalid_quantities_are_rejected(): void
    {
        $item = WarehouseItem::where('name', 'Boba')->first();
        foreach ([0, -5, 99999999] as $qty) {
            try {
                app(WarehouseService::class)->receive($item, $qty, $this->owner());
                $this->fail("Jumlah {$qty} harus ditolak");
            } catch (ValidationException) {
            }
        }
        $this->assertEquals(0, $item->fresh()->stock_current);
    }

    public function test_transfer_creates_ingredient_in_outlet_that_does_not_have_it(): void
    {
        $item = WarehouseItem::create(['name' => 'Bahan Baru', 'unit' => 'bungkus', 'stock_current' => 20]);
        $outlet = $this->outlet('Grafika');

        app(WarehouseService::class)->transfer($item, $outlet, 5, $this->owner());

        $this->assertEquals(5, Ingredient::where('outlet_id', $outlet->id)->where('name', 'Bahan Baru')->value('stock_current'));
        $this->assertEquals(15, $item->fresh()->stock_current);
    }

    public function test_unit_mismatch_is_rejected(): void
    {
        $item = WarehouseItem::where('name', 'Boba')->first();
        $item->update(['unit' => 'gram', 'stock_current' => 50]);
        $this->expectException(ValidationException::class);
        app(WarehouseService::class)->transfer($item, $this->outlet(), 5, $this->owner());
    }

    public function test_manager_takes_from_warehouse_on_stock_tab(): void
    {
        $outlet = $this->outlet();
        $manager = User::where('role', 'manager')->first();
        app(ShiftService::class)->open($manager, $outlet, 0);
        $this->actingAs($manager)->withSession(['pos_outlet_id' => $outlet->id]);
        $item = WarehouseItem::where('name', 'Boba')->first();
        $item->update(['stock_current' => 40]);

        Livewire::test(Pos::class)->set('tab', 'stok')
            ->assertSee('Ambil dari gudang')
            ->call('openTake', $item->id)
            ->set('takeQty', '999')->call('submitTake')->assertHasErrors('takeQty')   // melebihi stok gudang
            ->set('takeQty', '12')->call('submitTake')->assertHasNoErrors()->assertSet('takeOpen', false);

        $this->assertEquals(28, $item->fresh()->stock_current);
        $this->assertSame($manager->id, WarehouseMovement::first()->user_id);
    }

    public function test_cashier_cannot_take_from_warehouse(): void
    {
        $outlet = $this->outlet();
        $kasir = User::where('name', 'Elsya')->first();
        app(ShiftService::class)->open($kasir, $outlet, 0);
        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);
        $item = WarehouseItem::where('name', 'Boba')->first();
        $item->update(['stock_current' => 40]);
        $ing = Ingredient::where('outlet_id', $outlet->id)->where('name', 'Boba')->first();
        $before = $ing->stock_current;

        // 1) Tombol tidak tampil, 2) membuka form ditolak, 3) kirim langsung ditolak server.
        $lw = Livewire::test(Pos::class)->set('tab', 'stok')->assertDontSee('Ambil dari gudang');
        $lw->call('openTake', $item->id)->assertSet('takeOpen', false)->assertDispatched('toast');
        $lw->set('takeItemId', $item->id)->set('takeQty', '5')->call('submitTake')->assertForbidden();

        // 4) Bahkan lewat service, kasir tidak boleh (receive, adjust, transfer).
        foreach ([
            fn () => app(WarehouseService::class)->transfer($item, $outlet, 5, $kasir),
            fn () => app(WarehouseService::class)->receive($item, 5, $kasir),
            fn () => app(WarehouseService::class)->adjust($item, 99, $kasir),
        ] as $attempt) {
            try {
                $attempt();
                $this->fail('Kasir tidak boleh mengubah stok gudang');
            } catch (ValidationException $e) {
                $this->assertStringContainsString('owner atau manager', $e->validator->errors()->first());
            }
        }
        $this->assertEquals(40, $item->fresh()->stock_current);
        $this->assertEquals($before, $ing->fresh()->stock_current);
        $this->assertSame(0, WarehouseMovement::count());
    }

    public function test_admin_can_receive_and_send_via_panel_and_pages_render(): void
    {
        $this->actingAs($this->owner());
        $item = WarehouseItem::where('name', 'Boba')->first();

        Livewire::test(ManageWarehouseItems::class)
            ->callTableAction('receive', $item, ['qty' => 60, 'note' => 'Supplier A'])->assertHasNoTableActionErrors()
            ->callTableAction('send', $item, ['outlet_id' => $this->outlet('Grafika')->id, 'qty' => 25])->assertHasNoTableActionErrors()
            ->callTableAction('adjust', $item, ['qty' => 30, 'waste' => true])->assertHasNoTableActionErrors();

        $this->assertEquals(30, $item->fresh()->stock_current);
        $this->assertSame(['restock', 'transfer', 'waste'], WarehouseMovement::orderBy('created_at')->pluck('reason')->all());

        foreach (['/admin/warehouse-items', '/admin/warehouse-movements', '/admin/categories'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_ingredient_edit_form_accepts_bungkus_unit(): void
    {
        $this->actingAs($this->owner());
        $ing = Ingredient::where('outlet_id', $this->outlet()->id)->where('unit', 'bungkus')->first();
        Livewire::test(\App\Filament\Resources\IngredientResource\Pages\ManageIngredients::class)
            ->callTableAction('edit', $ing, ['stock_min' => 7])->assertHasNoTableActionErrors();
        $this->assertEquals(7, $ing->fresh()->stock_min);
    }
}
