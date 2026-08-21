<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // Admin
        User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Manager
        User::updateOrCreate(
            ['email' => 'manager@erp.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('password'),
                'role' => 'manager',
            ]
        );

        // Staff
        User::updateOrCreate(
            ['email' => 'staff@erp.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'role' => 'staff',
            ]
        );
    }
}