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
