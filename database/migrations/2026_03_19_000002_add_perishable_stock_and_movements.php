<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('is_perishable')->default(false)->after('is_active');
            $table->unsignedInteger('shelf_life_days')->nullable()->after('is_perishable');
        });

        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->boolean('stock_deducted')->default(false)->after('released_at');
            $table->timestamp('paid_at')->nullable()->after('stock_deducted');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // stock_in, release, payment_sale, adjustment
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity_change', 12, 2);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropColumn(['stock_deducted', 'paid_at']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['is_perishable', 'shelf_life_days']);
        });
    }
};
