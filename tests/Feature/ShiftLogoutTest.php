<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftLogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cashier_cannot_logout_until_shift_is_closed(): void
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $kasir = User::where('name', 'Elsya')->first();
        $shift = app(ShiftService::class)->open($kasir, $outlet, 0);
        $this->actingAs($kasir)->withSession(['pos_outlet_id' => $outlet->id]);

        $this->get(route('pos'))->assertOk()->assertSee('Tutup shift dulu sebelum keluar');
        $this->post(route('pos.logout'))->assertRedirect(route('pos'))->assertSessionHas('logout_error');
        $this->assertAuthenticatedAs($kasir);

        app(ShiftService::class)->close($shift, $kasir, 0);
        $this->post(route('pos.logout'))->assertRedirect(route('pos.login'));
        $this->assertGuest();
    }

    public function test_manager_can_logout_with_open_shift(): void
    {
        $outlet = Outlet::where('name', 'Tembalang')->first();
        $manager = User::where('role', 'manager')->first();
        app(ShiftService::class)->open($manager, $outlet, 0);
        $this->actingAs($manager)->withSession(['pos_outlet_id' => $outlet->id]);

        $this->post(route('pos.logout'))->assertRedirect(route('pos.login'));
        $this->assertGuest();
    }
}
