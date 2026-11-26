<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->string('client_email')->nullable()->after('client_office');
            $table->timestamp('paid_notice_sent_at')->nullable()->after('paid_at');
        });

        Schema::table('request_installments', function (Blueprint $table) {
            $table->timestamp('due_reminder_sent_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropColumn(['client_email', 'paid_notice_sent_at']);
        });

        Schema::table('request_installments', function (Blueprint $table) {
            $table->dropColumn('due_reminder_sent_at');
        });
    }
};
