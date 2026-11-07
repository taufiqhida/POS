<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Owner/manager menandai catatan penting (mis. ganti metode bayar) sebagai "sudah dicek". */
    public function up(): void
    {
        if (Schema::hasColumn('audit_logs', 'reviewed_at')) {
            return;
        }
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
        });
    }
};
