<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            // Judul di bagian atas nota (mis. "Jelly Potter Grafika"). Kosong = nama toko + nama outlet.
            $table->string('receipt_name')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('outlets', fn (Blueprint $table) => $table->dropColumn('receipt_name'));
    }
};
