<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')->where('role', 'requester')->update(['role' => 'staff']);
    }

    public function down(): void
    {
        // Requester role was removed; previous requester accounts cannot be distinguished from staff.
    }
};
