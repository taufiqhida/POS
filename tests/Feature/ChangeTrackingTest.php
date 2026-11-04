<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Models\MenuVariant;
use App\Models\Topping;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Services\WarehouseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_seeding_and_system_changes_without_login_are_not_logged(): void
    {
        $this->assertSame(0, AuditLog::whereIn('action', ['master_price', 'master_edit', 'stock_update'])->count());
        Menu::first()->update(['base_price' => 99000]);
        $this->assertSame(0, AuditLog::whereIn('action', ['master_price', 'master_edit'])->count());
    }

    public function test_manager_price_change_is_logged_with_old_and_new_value(): void
    {
        $manager = User::where('role', 'manager')->first();
        $this->actingAs($manager);
        $menu = Menu::where('name', 'Waffle Pandan')->first();
        $old = $menu->base_price;

        $menu->update(['base_price' => $old + 2000]);

        $log = AuditLog::where('action', 'master_price')->latest()->first();
        $this->assertSame($manager->id, $log->user_id);
        $this->assertStringContainsString('Waffle Pandan', $log->detail);
        $this->assertStringContainsString('Rp'.number_format($old, 0, ',', '.').' → Rp'.number_format($old + 2000, 0, ',', '.'), $log->detail);
    }

    public function test_variant_topping_and_non_price_edits_are_logged(): void
    {
        $this->actingAs(User::where('role', 'manager')->first());
        $variant = MenuVariant::first();
        $variant->update(['price' => $variant->price + 1000]);
        Topping::first()->update(['price' => 7777]);
        Menu::first()->update(['is_active' => false]);

        $this->assertSame(2, AuditLog::where('action', 'master_price')->count());
        $this->assertStringContainsString('Rp7.777', AuditLog::where('action', 'master_price')->where('detail', 'like', 'Topping%')->first()->detail);
        $edit = AuditLog::where('action', 'master_edit')->first();
        $this->assertStringContainsString('Tersedia: ya → tidak', $edit->detail);
    }

    public function test_unchanged_save_logs_nothing(): void
    {
        $this->actingAs(User::where('role', 'manager')->first());
        $menu = Menu::first();
        $menu->update(['base_price' => $menu->base_price]);
        $this->assertSame(0, AuditLog::where('action', 'like', 'master_%')->count());
    }

    public function test_stock_changes_are_logged_for_warehouse_and_ingredients(): void
    {
        $manager = User::where('role', 'manager')->first();
        $this->actingAs($manager);
        $item = WarehouseItem::where('name', 'Boba')->first();

        app(WarehouseService::class)->receive($item, 50, $manager, 'Nota 1');
        $this->assertStringContainsString('Gudang "Boba": +50', AuditLog::where('action', 'stock_update')->latest()->first()->detail);

        $ing = Ingredient::first();
        $ing->update(['stock_current' => $ing->stock_current + 5, 'stock_min' => 3]);
        $log = AuditLog::where('action', 'stock_update')->where('detail', 'like', 'Bahan "%')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Stok:', $log->detail);
        $this->assertStringContainsString('Batas kritis', $log->detail);
        $this->assertSame($manager->id, $log->user_id);
    }

    public function test_history_page_shows_changes_to_the_owner(): void
    {
        $owner = User::where('role', 'owner')->first();
        $this->actingAs($owner);
        Menu::where('name', 'Waffle Pandan')->first()->update(['base_price' => 31000]);

        $this->get('/admin/audit-logs')->assertOk()->assertSee('Riwayat Aktivitas');
    }
}
