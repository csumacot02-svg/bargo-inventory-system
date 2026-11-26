<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_number')->nullable()->unique()->after('employee_id');
            $table->string('religion')->nullable()->after('id_number');
            $table->string('sex')->nullable()->after('religion');
            $table->date('date_of_birth')->nullable()->after('sex');
            $table->string('birth_place')->nullable()->after('date_of_birth');
            $table->string('avatar')->nullable()->after('birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['id_number', 'religion', 'sex', 'date_of_birth', 'birth_place', 'avatar']);
        });
    }
};
