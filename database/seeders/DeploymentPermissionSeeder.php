<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeploymentPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'deployment_view',
                'label' => 'View Deployment Manager',
                'module' => 'deployment',
                'description' => 'View deployment status and history.',
            ],
            [
                'name' => 'deployment_upload',
                'label' => 'Upload Deployment Package',
                'module' => 'deployment',
                'description' => 'Upload and validate release packages.',
            ],
            [
                'name' => 'deployment_install',
                'label' => 'Install Deployment',
                'module' => 'deployment',
                'description' => 'Install validated release packages.',
            ],
            [
                'name' => 'deployment_rollback',
                'label' => 'Rollback Deployment',
                'module' => 'deployment',
                'description' => 'Rollback an installed release.',
            ],
            [
                'name' => 'deployment_manage',
                'label' => 'Manage Deployment',
                'module' => 'deployment',
                'description' => 'Manage deployment configuration and registry.',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'label' => $permission['label'],
                    'module' => $permission['module'],
                    'description' => $permission['description'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->where('module', 'deployment')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role' => 'admin',
                    'permission_id' => $permissionId,
                ],
                [
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}