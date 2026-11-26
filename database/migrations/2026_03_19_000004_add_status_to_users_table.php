<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('pending')->after('role');
            }
        });

        // Preserve existing records: map is_active → status without deleting users.
        if (Schema::hasColumn('users', 'is_active')) {
            DB::table('users')->where('is_active', true)->update(['status' => User::STATUS_ACTIVE]);
            DB::table('users')->where('is_active', false)->update(['status' => User::STATUS_SUSPENDED]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
