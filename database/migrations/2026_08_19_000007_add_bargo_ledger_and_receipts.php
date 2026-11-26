<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->string('or_number')->nullable()->unique()->after('paid_at');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('or_number');
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('notes');
            $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->after('unit_cost');
        });

        Schema::create('inventory_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('stock_ins')->nullOnDelete();
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_remaining', 12, 2);
            $table->date('expiration_date')->nullable();
            $table->string('source')->default('stock_in');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_lots');

        Schema::table('stock_in_items', function (Blueprint $table) {
            $table->dropColumn('expiration_date');
        });

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('voided_by');
            $table->dropColumn(['voided_at', 'void_reason']);
        });

        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropColumn(['or_number', 'amount_paid']);
        });
    }
};
