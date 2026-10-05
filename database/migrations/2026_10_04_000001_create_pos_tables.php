<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->default('Minuman');
            $table->unsignedInteger('base_price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_id')->constrained()->cascadeOnDelete();
            $table->string('size');
            $table->unsignedInteger('price');
            $table->timestamps();
        });

        Schema::create('toppings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ingredients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('unit');
            $table->decimal('stock_current', 12, 2)->default(0);
            $table->decimal('stock_min', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('variant_id')->nullable()->constrained('menu_variants')->cascadeOnDelete();
            $table->foreignUuid('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 12, 2);
            $table->timestamps();
        });

        // Bahan yang dipakai topping, dicocokkan per outlet lewat nama bahan.
        Schema::create('topping_recipes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('topping_id')->constrained()->cascadeOnDelete();
            $table->string('ingredient_name');
            $table->decimal('qty', 12, 2);
            $table->timestamps();
        });

        Schema::create('promos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // percent | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_subtotal')->default(0);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained();
            $table->foreignUuid('opened_by')->constrained('users');
            $table->foreignUuid('closed_by')->nullable()->constrained('users');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_cash');
            $table->integer('expected_cash')->nullable();
            $table->integer('actual_cash')->nullable();
            $table->integer('cash_difference')->nullable();
            $table->string('status')->default('open');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('shift_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('outlet_id')->constrained();
            $table->foreignUuid('shift_id')->constrained();
            $table->foreignUuid('cashier_id')->constrained('users');
            $table->string('payment_method'); // cash | qris | debit | digital
            $table->string('channel')->default('offline'); // offline | gofood | grabfood | shopeefood
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->string('discount_label')->nullable();
            $table->unsignedInteger('total');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->unsignedInteger('change_amount')->default(0);
            $table->string('status')->default('paid'); // paid | void | refunded
            $table->timestamps();
            $table->index(['outlet_id', 'created_at']);
        });

        Schema::create('transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('menu_id')->constrained();
            $table->foreignUuid('variant_id')->nullable()->constrained('menu_variants');
            $table->string('menu_name');
            $table->string('size')->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('price_each');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('transaction_item_toppings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('transaction_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('topping_id')->constrained();
            $table->string('topping_name');
            $table->unsignedInteger('price_each');
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained();
            $table->foreignUuid('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_change', 12, 2);
            $table->string('reason'); // sale | restock | adjustment | waste | void
            $table->string('reference_id')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('outlet_id')->constrained();
            $table->foreignUuid('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained();
            $table->decimal('system_qty', 12, 2);
            $table->decimal('physical_qty', 12, 2);
            $table->decimal('difference', 12, 2);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->foreignUuid('approved_by')->nullable()->constrained('users');
            $table->string('action');
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('detail')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'audit_logs', 'stock_counts', 'stock_movements', 'transaction_item_toppings', 'transaction_items',
            'transactions', 'expenses', 'shifts', 'promos', 'topping_recipes', 'recipes', 'ingredients', 'toppings',
            'menu_variants', 'menus'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
