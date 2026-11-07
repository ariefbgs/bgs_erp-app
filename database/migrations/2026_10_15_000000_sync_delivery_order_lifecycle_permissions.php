<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions') ||
            !Schema::hasTable('role_permissions')) {
            throw new RuntimeException('Permission tables are missing.');
        }

        $now = now();

        $permissions = [
            [
                'name' => 'delivery_order_mark_delivered',
                'label' => 'Mark Delivery Order Delivered',
                'module' => 'delivery_order',
                'description' => 'Mark a Pending Delivery Order as Delivered.',
            ],
            [
                'name' => 'delivery_order_reopen',
                'label' => 'Reopen Delivery Order',
                'module' => 'delivery_order',
                'description' => 'Correction permission to reopen a Delivered Delivery Order to Pending.',
            ],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission['name'])
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert(
                    array_merge($permission, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'delivery_order_mark_delivered')
            ->value('id');

        if (!$permissionId) {
            throw new RuntimeException('Delivery permission provisioning failed.');
        }

        DB::table('role_permissions')->insertOrIgnore([
            'role' => 'staff',
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Preserve role assignments. Reversal requires a separate review.
    }
};