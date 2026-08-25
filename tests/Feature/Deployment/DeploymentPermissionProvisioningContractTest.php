<?php

namespace Tests\Feature\Deployment;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DeploymentPermissionProvisioningContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_database_seeder_assigns_all_deployment_permissions_to_admin_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $rows = DB::table('role_permissions')
            ->join(
                'permissions',
                'permissions.id',
                '=',
                'role_permissions.permission_id'
            )
            ->where('permissions.module', 'deployment')
            ->get();

        $this->assertCount(5, $rows);

        $this->assertSame(
            ['admin'],
            $rows->pluck('role')->unique()->values()->all()
        );

        $this->assertSame(
            5,
            $rows->pluck('permission_id')->unique()->count()
        );
    }
}