<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('released_at');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('completed_by');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['completed_at', 'cancelled_at']);
        });
    }
};
