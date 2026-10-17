<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Menu yang dihapus disembunyikan, bukan dibuang — riwayat transaksi tetap merujuk ke menu itu. */
    public function up(): void
    {
        Schema::table('menus', fn (Blueprint $table) => $table->softDeletes());
    }

    public function down(): void
    {
        Schema::table('menus', fn (Blueprint $table) => $table->dropSoftDeletes());
    }
};
