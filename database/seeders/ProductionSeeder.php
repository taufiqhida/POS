<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Data awal untuk server sungguhan: outlet, pengaturan toko, dan menu.
 * TIDAK membuat akun — buat akun asli dengan `php artisan jp:user`.
 *
 *   php artisan db:seed --class=ProductionSeeder --force
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        Outlet::firstOrCreate(['name' => 'Tembalang'], ['address' => 'Tembalang, Semarang']);
        Outlet::firstOrCreate(['name' => 'Grafika'], ['address' => 'Jl. Grafika, Semarang']);

        foreach (Setting::DEFAULTS as $k => $v) {
            if (Setting::find($k) === null) {
                Setting::put($k, $v);
            }
        }

        $this->call(MenuSeeder::class);
    }
}
