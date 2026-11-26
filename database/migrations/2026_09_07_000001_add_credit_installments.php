<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->string('installment_frequency')->nullable()->after('due_date'); // daily, monthly, once
            $table->unsignedSmallInteger('installment_count')->nullable()->after('installment_frequency');
            $table->date('installment_start_date')->nullable()->after('installment_count');
        });

        Schema::create('request_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, partial, paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_request_id', 'sequence']);
            $table->index(['due_date', 'status']);
        });

        Schema::create('request_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->string('or_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_payments');
        Schema::dropIfExists('request_installments');

        Schema::table('inventory_requests', function (Blueprint $table) {
            $table->dropColumn(['installment_frequency', 'installment_count', 'installment_start_date']);
        });
    }
};
