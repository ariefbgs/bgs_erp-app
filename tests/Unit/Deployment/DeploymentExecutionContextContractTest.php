<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DeploymentExecutionContextContractTest
    extends TestCase
{
    public function test_execution_context_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                'App\Services\Deployment\DeploymentExecutionContext'
            ),
            'DeploymentExecutionContext is required.'
        );
    }

    public function test_execution_context_provider_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                'App\Services\Deployment\DeploymentExecutionContextProviderInterface'
            ),
            'DeploymentExecutionContextProviderInterface is required.'
        );
    }

    public function test_execution_context_exposes_required_paths(): void
    {
        $class =
            'App\Services\Deployment\DeploymentExecutionContext';

        $this->assertTrue(
            class_exists($class),
            'DeploymentExecutionContext is required.'
        );

        $reflection =
            new ReflectionClass($class);

        foreach ([
            'packagePath',
            'stagingPath',
            'applicationRoot',
            'recoveryPath',
            'expectedPackageSha256',
            'expectedManifestIdentity',
        ] as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Execution context missing {$method}()."
            );
        }
    }

    public function test_provider_resolves_context_by_release_id(): void
    {
        $interface =
            'App\Services\Deployment\DeploymentExecutionContextProviderInterface';

        $this->assertTrue(
            interface_exists($interface),
            'DeploymentExecutionContextProviderInterface is required.'
        );

        $reflection =
            new ReflectionClass($interface);

        $this->assertTrue(
            $reflection->hasMethod('forRelease'),
            'Execution context provider must expose forRelease().'
        );
    }

    public function test_orchestrator_requires_execution_context_provider(): void
    {
        $class =
            \App\Services\Deployment\DeploymentOrchestrator::class;

        $reflection =
            new ReflectionClass($class);

        $constructor =
            $reflection->getConstructor();

        $this->assertNotNull(
            $constructor,
            'Orchestrator constructor is required.'
        );

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (
                $type !== null
                && method_exists($type, 'getName')
            ) {
                $dependencies[] = $type->getName();
            }
        }

        $this->assertContains(
            'App\Services\Deployment\DeploymentExecutionContextProviderInterface',
            $dependencies,
            'Orchestrator must resolve runtime paths through execution context provider.'
        );
    }
}
