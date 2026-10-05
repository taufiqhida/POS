<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Ditandai owner sebagai "Best menu" — tampil di tab ⭐ Best layar kasir.
            $table->boolean('is_best')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('menus', fn (Blueprint $table) => $table->dropColumn('is_best'));
    }
};
