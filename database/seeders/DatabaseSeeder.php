<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            DeploymentPermissionSeeder::class,
        ]);
    }
}