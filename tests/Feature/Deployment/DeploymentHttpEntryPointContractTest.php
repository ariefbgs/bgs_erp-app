<?php

namespace Tests\Feature\Deployment;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DeploymentHttpEntryPointContractTest extends TestCase
{
    private array $expectedRoutes = [
        'deployment.index' => [
            'method' => 'GET',
            'permission' => 'permission:deployment_view',
        ],
        'deployment.upload' => [
            'method' => 'POST',
            'permission' => 'permission:deployment_upload',
        ],
        'deployment.install' => [
            'method' => 'POST',
            'permission' => 'permission:deployment_install',
        ],
        'deployment.rollback' => [
            'method' => 'POST',
            'permission' => 'permission:deployment_rollback',
        ],
        'deployment.history' => [
            'method' => 'GET',
            'permission' => 'permission:deployment_view',
        ],
    ];

    public function test_controlled_deployment_routes_exist(): void
    {
        foreach ($this->expectedRoutes as $name => $contract) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull(
                $route,
                "Missing deployment route [{$name}]"
            );
        }
    }

    public function test_deployment_routes_use_expected_http_methods(): void
    {
        foreach ($this->expectedRoutes as $name => $contract) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull(
                $route,
                "Missing deployment route [{$name}]"
            );

            $this->assertContains(
                $contract['method'],
                $route->methods(),
                "Route [{$name}] must allow {$contract['method']}"
            );
        }
    }

    public function test_deployment_routes_are_authenticated(): void
    {
        foreach ($this->expectedRoutes as $name => $contract) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull(
                $route,
                "Missing deployment route [{$name}]"
            );

            $this->assertContains(
                'auth',
                $route->gatherMiddleware(),
                "Route [{$name}] must require authentication"
            );
        }
    }

    public function test_deployment_routes_have_explicit_permission_boundaries(): void
    {
        foreach ($this->expectedRoutes as $name => $contract) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull(
                $route,
                "Missing deployment route [{$name}]"
            );

            $this->assertContains(
                $contract['permission'],
                $route->gatherMiddleware(),
                "Route [{$name}] missing permission boundary [{$contract['permission']}]"
            );
        }
    }

    public function test_no_deployment_route_uses_get_for_mutation(): void
    {
        foreach ([
            'deployment.upload',
            'deployment.install',
            'deployment.rollback',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull(
                $route,
                "Missing deployment mutation route [{$name}]"
            );

            $this->assertNotContains(
                'GET',
                $route->methods(),
                "Deployment mutation route [{$name}] must never use GET"
            );
        }
    }
}