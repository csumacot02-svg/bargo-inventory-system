<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->string('client_name')->nullable()->after('purpose');
            $table->string('client_id_number')->nullable()->after('client_name');
            $table->string('client_office')->nullable()->after('client_id_number');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropColumn(['client_name', 'client_id_number', 'client_office']);
        });
    }
};
