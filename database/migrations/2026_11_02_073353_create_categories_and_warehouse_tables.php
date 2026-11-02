<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Kategori menu yang bisa diatur owner ----
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Kategori yang sudah dipakai menu otomatis menjadi data kategori (urutan sesuai kemunculan).
        $order = 0;
        foreach (DB::table('menus')->orderBy('created_at')->pluck('category')->unique() as $name) {
            DB::table('categories')->insert(['id' => (string) Str::uuid(), 'name' => $name, 'sort_order' => $order += 10,
                'created_at' => now(), 'updated_at' => now()]);
        }

        // ---- Gudang utama ----
        Schema::create('warehouse_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();   // sama dengan nama bahan di outlet — dipakai untuk mencocokkan
            $table->string('unit');
            $table->decimal('stock_current', 12, 2)->default(0);
            $table->decimal('stock_min', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('warehouse_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('warehouse_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->nullable()->constrained()->nullOnDelete(); // outlet tujuan (bila diambil)
            $table->decimal('qty_change', 12, 2);
            $table->string('reason'); // restock | transfer | adjustment | waste
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['warehouse_item_id', 'created_at']);
        });

        // Bahan yang sudah ada di outlet otomatis punya "kartu" di gudang (stok awal 0, isi lewat Barang masuk).
        foreach (DB::table('ingredients')->orderBy('created_at')->get(['name', 'unit'])->unique('name') as $i) {
            DB::table('warehouse_items')->insert(['id' => (string) Str::uuid(), 'name' => $i->name, 'unit' => $i->unit,
                'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');
        Schema::dropIfExists('warehouse_items');
        Schema::dropIfExists('categories');
    }
};
