<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'delivery_order_mark_delivered',
                'label' => 'Mark Delivery Order Delivered',
                'module' => 'delivery_order',
                'description' =>
                    'Mark a Pending Delivery Order as Delivered.',
            ],
            [
                'name' => 'delivery_order_reopen',
                'label' => 'Reopen Delivery Order',
                'module' => 'delivery_order',
                'description' =>
                    'Correction permission to reopen a Delivered Delivery Order to Pending.',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge(
                    $permission,
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                )
            );
        }

        $permissionId = DB::table('permissions')
            ->where(
                'name',
                'delivery_order_mark_delivered'
            )
            ->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role' => 'staff',
                    'permission_id' => $permissionId,
                ],
                [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')
            ->whereIn(
                'name',
                [
                    'delivery_order_mark_delivered',
                    'delivery_order_reopen',
                ]
            )
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('role_permissions')
                ->whereIn('permission_id', $ids)
                ->delete();

            DB::table('permissions')
                ->whereIn('id', $ids)
                ->delete();
        }
    }
};
