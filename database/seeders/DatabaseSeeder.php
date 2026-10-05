<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal. Akun & PIN di bawah hanya untuk percobaan — GANTI setelah dipakai sungguhan.
     */
    public function run(): void
    {
        $tembalang = Outlet::create(['name' => 'Tembalang', 'address' => 'Tembalang, Semarang']);
        $grafika = Outlet::create(['name' => 'Grafika', 'address' => 'Jl. Grafika, Semarang']);

        User::create(['name' => 'Owner', 'role' => 'owner', 'email' => 'owner@jellypotter.test', 'password' => 'password', 'pin' => '1234', 'can_change_price' => true]);
        User::create(['name' => 'Manager', 'role' => 'manager', 'email' => 'manager@jellypotter.test', 'password' => 'password', 'pin' => '5678', 'can_change_price' => true]);
        // Grafika: Eka, Pasya — Tembalang: Elsya, Mia
        User::create(['name' => 'Eka', 'role' => 'kasir', 'outlet_id' => $grafika->id, 'pin' => '1111']);
        User::create(['name' => 'Pasya', 'role' => 'kasir', 'outlet_id' => $grafika->id, 'pin' => '4444']);
        User::create(['name' => 'Elsya', 'role' => 'kasir', 'outlet_id' => $tembalang->id, 'pin' => '2222']);
        User::create(['name' => 'Mia', 'role' => 'kasir', 'outlet_id' => $tembalang->id, 'pin' => '3333']);

        $this->call(MenuSeeder::class);

        foreach (Setting::DEFAULTS as $k => $v) {
            Setting::put($k, $v);
        }
    }
}
