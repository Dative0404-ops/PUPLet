<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run in order: the catalog comes first because the demo accounts link to it.
        $this->call([
            LetCatalogSeeder::class,
            DemoStudentSeeder::class,
            DemoAdminSeeder::class,
        ]);
    }
}
