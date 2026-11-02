<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->unique();
            $table->string('name');
            $table->string('pin_hash');
            $table->unsignedTinyInteger('pin_failed')->default(0);
            $table->unsignedInteger('stamps')->default(0);
            $table->unsignedInteger('stamps_lifetime')->default(0);
            $table->unsignedInteger('visits')->default(0);
            $table->boolean('is_staff')->default(false);
            $table->boolean('can_contact')->default(false);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stamp_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('change');
            $table->string('reason');   // earn | redeem | reverse | expire | adjust
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->unsignedTinyInteger('stamps')->default(1)->after('free_toppings');
            $table->boolean('is_reward')->default(false)->after('is_best');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('customer_id')->nullable()->after('cashier_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('stamps_earned')->default(0);
            $table->unsignedInteger('stamps_used')->default(0);
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->boolean('is_reward')->default(false);
        });

        // Cap awal per menu menurut kategori & harga (owner bebas mengubah di Admin > Menu).
        Menu::withTrashed()->get()->each(fn (Menu $m) => $m->forceFill(['stamps' => Menu::defaultStamps($m->category, (int) $m->base_price)])->saveQuietly());
        DB::table('settings')->updateOrInsert(['key' => 'stamps_per_reward'], ['value' => '20']);
    }

    public function down(): void
    {
        Schema::table('transaction_items', fn (Blueprint $t) => $t->dropColumn('is_reward'));
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
            $t->dropColumn(['stamps_earned', 'stamps_used']);
        });
        Schema::table('menus', fn (Blueprint $t) => $t->dropColumn(['stamps', 'is_reward']));
        Schema::dropIfExists('stamp_logs');
        Schema::dropIfExists('customers');
    }
};
