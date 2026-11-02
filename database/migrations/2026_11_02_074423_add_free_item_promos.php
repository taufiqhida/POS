<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Promo "beli X gratis Y" (type = free_item): pemicu & hadiah berupa kategori dan/atau menu. */
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->unsignedInteger('value')->default(0)->change();   // promo item tidak punya nilai persen/rupiah
            $table->json('trigger_categories')->nullable();
            $table->json('trigger_menus')->nullable();
            $table->unsignedTinyInteger('buy_qty')->default(0);
            $table->json('reward_categories')->nullable();
            $table->json('reward_menus')->nullable();
            $table->unsignedTinyInteger('reward_qty')->default(1);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->string('promo_name')->nullable()->after('subtotal'); // terisi = item gratis dari promo ini
        });
    }

    public function down(): void
    {
        Schema::table('transaction_items', fn (Blueprint $table) => $table->dropColumn('promo_name'));
        Schema::table('promos', fn (Blueprint $table) => $table->dropColumn(
            ['trigger_categories', 'trigger_menus', 'buy_qty', 'reward_categories', 'reward_menus', 'reward_qty']));
    }
};
