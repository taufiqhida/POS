<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Grup topping: menu hanya bisa memakai topping dari grupnya sendiri (minuman / waffle / makanan). */
    public function up(): void
    {
        Schema::table('toppings', function (Blueprint $table) {
            $table->string('group')->default('minuman')->after('name');
        });
        Schema::table('menus', function (Blueprint $table) {
            $table->string('topping_group')->default('minuman')->after('free_toppings');
        });
    }

    public function down(): void
    {
        Schema::table('toppings', fn (Blueprint $table) => $table->dropColumn('group'));
        Schema::table('menus', fn (Blueprint $table) => $table->dropColumn('topping_group'));
    }
};
