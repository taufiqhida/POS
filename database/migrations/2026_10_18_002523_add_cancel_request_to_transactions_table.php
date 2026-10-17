<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan void/refund dari kasir. Transaksi tetap "paid" (masih dihitung) sampai
     * owner/manager menyetujui di panel admin.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('cancel_request')->nullable()->after('status'); // void | refund
            $table->string('cancel_reason')->nullable()->after('cancel_request');
            $table->foreignUuid('cancel_requested_by')->nullable()->after('cancel_reason')->constrained('users');
            $table->timestamp('cancel_requested_at')->nullable()->after('cancel_requested_by');
            $table->index('cancel_request');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancel_requested_by');
            $table->dropIndex(['cancel_request']);
            $table->dropColumn(['cancel_request', 'cancel_reason', 'cancel_requested_at']);
        });
    }
};
