<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BgsDeploymentAuthorizationContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_has_all_deployment_permissions(): void
    {
        $user = new User();
        $user->role = 'admin';

        foreach ([
            'deployment_view',
            'deployment_upload',
            'deployment_install',
            'deployment_rollback',
            'deployment_manage',
        ] as $permission) {
            $this->assertTrue(
                $user->hasPermission($permission),
                "Admin missing permission: {$permission}"
            );
        }
    }

    public function test_non_admin_roles_do_not_receive_deployment_permissions(): void
    {
        foreach (['manager', 'staff', 'user'] as $role) {
            $user = new User();
            $user->role = $role;

            foreach ([
                'deployment_view',
                'deployment_upload',
                'deployment_install',
                'deployment_rollback',
                'deployment_manage',
            ] as $permission) {
                $this->assertFalse(
                    $user->hasPermission($permission),
                    "{$role} unexpectedly has {$permission}"
                );
            }
        }
    }

    public function test_deployment_permission_assignment_is_exactly_admin_only(): void
    {
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
    }

    public function test_permission_middleware_allows_admin(): void
    {
        $user = new User();
        $user->role = 'admin';

        $request = Request::create(
            '/deployment-test',
            'GET'
        );

        $request->setUserResolver(
            fn () => $user
        );

        $middleware = new CheckPermission();

        $response = $middleware->handle(
            $request,
            fn () => response('AUTHORIZED', 200),
            'deployment_view'
        );

        $this->assertSame(
            200,
            $response->getStatusCode()
        );
    }

    public function test_permission_middleware_rejects_manager(): void
    {
        $user = new User();
        $user->role = 'manager';

        $request = Request::create(
            '/deployment-test',
            'GET'
        );

        $request->setUserResolver(
            fn () => $user
        );

        $middleware = new CheckPermission();

        $this->expectException(
            \Symfony\Component\HttpKernel\Exception\HttpException::class
        );

        $middleware->handle(
            $request,
            fn () => response('AUTHORIZED', 200),
            'deployment_view'
        );
    }
}