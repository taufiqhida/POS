<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Jumlah topping gratis per cup (mis. "harga sudah termasuk free 1 topping").
            $table->unsignedTinyInteger('free_toppings')->default(0)->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('free_toppings');
        });
    }
};
