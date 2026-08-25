<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DeploymentHistoryRepositoryInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DeploymentHistoryRepositoryPersistenceContractTest
    extends TestCase
{
    private string $class =
        'App\Services\Deployment\DatabaseDeploymentHistoryRepository';

    public function test_history_repository_interface_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                DeploymentHistoryRepositoryInterface::class
            )
        );
    }

    public function test_database_history_repository_exists(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'Concrete database deployment-history repository is required.'
        );
    }

    public function test_database_repository_implements_history_contract(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'Concrete database deployment-history repository is required.'
        );

        $reflection =
            new ReflectionClass($this->class);

        $this->assertTrue(
            $reflection->implementsInterface(
                DeploymentHistoryRepositoryInterface::class
            ),
            'Database history repository must implement the canonical interface.'
        );
    }

    public function test_database_repository_exposes_all_interface_methods(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'Concrete database deployment-history repository is required.'
        );

        $interface =
            new ReflectionClass(
                DeploymentHistoryRepositoryInterface::class
            );

        $repository =
            new ReflectionClass($this->class);

        foreach ($interface->getMethods() as $method) {
            $this->assertTrue(
                $repository->hasMethod(
                    $method->getName()
                ),
                'Missing history method: ' .
                    $method->getName()
            );
        }
    }

    public function test_repository_targets_deployment_history_persistence(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'Concrete database deployment-history repository is required.'
        );

        $reflection =
            new ReflectionClass($this->class);

        $file =
            $reflection->getFileName();

        $this->assertIsString($file);

        $source =
            file_get_contents($file);

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'deployment_history',
            $source,
            'History repository must persist to deployment_history.'
        );
    }
}