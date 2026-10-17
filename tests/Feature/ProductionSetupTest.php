<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeder_creates_menu_but_no_demo_accounts(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class); // aman dijalankan ulang

        $this->assertSame(['Grafika', 'Tembalang'], Outlet::orderBy('name')->pluck('name')->all());
        $this->assertSame(0, User::count());
        $this->assertGreaterThan(100, Menu::count());
    }

    public function test_menu_seeder_refuses_to_overwrite_live_data_in_production(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $shift = app(\App\Services\ShiftService::class)->open($kasir, $outlet, 0);
        $menu = Menu::where('outlet_id', $outlet->id)->where('name', 'Jasmine Tea')->first();
        app(\App\Services\PosService::class)->checkout($kasir, $shift,
            [['menu_id' => $menu->id, 'variant_id' => null, 'topping_ids' => [], 'qty' => 1]], 'qris', 'offline', 0);

        // Owner mengubah data lewat web.
        $menu->update(['base_price' => 6000]);
        $custom = Menu::create(['outlet_id' => $outlet->id, 'name' => 'Menu Owner', 'category' => 'Baru', 'base_price' => 9000]);

        $this->app['env'] = 'production';
        app(\Database\Seeders\MenuSeeder::class)->run();

        $this->assertSame(6000, $menu->fresh()->base_price);
        $this->assertTrue($custom->fresh()->is_active);
    }

    public function test_jp_user_creates_owner_and_cashier(): void
    {
        $this->seed(ProductionSeeder::class);
        $grafika = Outlet::where('name', 'Grafika')->first();

        $this->artisan('jp:user')
            ->expectsChoice('Peran', 'owner', User::ROLES)
            ->expectsQuestion('Nama', 'Pemilik')
            ->expectsQuestion('Email login panel admin', 'pemilik@example.com')
            ->expectsQuestion('Password panel admin (min. 8 karakter)', 'rahasia-panjang')
            ->expectsQuestion('PIN kasir (4–6 angka)', '908172')
            ->assertSuccessful();

        $this->artisan('jp:user')
            ->expectsChoice('Peran', 'kasir', User::ROLES)
            ->expectsQuestion('Nama', 'Eka')
            ->expectsChoice('Outlet tugas', $grafika->id, Outlet::orderBy('name')->pluck('name', 'id')->all())
            ->expectsQuestion('PIN kasir (4–6 angka)', '4321')
            ->assertSuccessful();

        $owner = User::where('role', 'owner')->first();
        $this->assertTrue($owner->checkPin('908172'));
        $this->assertTrue($owner->canAccessPanel(filament()->getPanel('admin')));
        $this->assertSame($grafika->id, User::where('name', 'Eka')->value('outlet_id'));
    }
}
