<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed reference data only.
     * Does NOT create default admin/staff accounts.
     * Create the first administrator via /setup/admin.
     */
    public function run(): void
    {
        $this->call(SampleCatalogSeeder::class);
    }
}
