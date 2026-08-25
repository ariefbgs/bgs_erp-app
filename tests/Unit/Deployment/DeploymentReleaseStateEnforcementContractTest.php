<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DeploymentReleaseStateRepositoryInterface;
use App\Services\Deployment\DeploymentStateTransitionPolicy;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;

final class DeploymentReleaseStateEnforcementContractTest extends TestCase
{
    public function test_release_state_repository_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                DeploymentReleaseStateRepositoryInterface::class
            )
        );
    }

    public function test_repository_exposes_only_canonical_forward_mutations_currently_supported(): void
    {
        $reflection = new ReflectionClass(
            DeploymentReleaseStateRepositoryInterface::class
        );

        foreach (
            [
                'status',
                'markValidated',
                'markInstalled',
                'markFailed',
            ] as $method
        ) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Missing repository method: {$method}"
            );
        }
    }

    public function test_database_release_state_repository_exists(): void
    {
        $this->assertTrue(
            class_exists(
                'App\Services\Deployment\DatabaseDeploymentReleaseStateRepository'
            ),
            'Concrete database release-state repository is required.'
        );
    }

    public function test_database_repository_implements_release_state_contract(): void
    {
        $class =
            'App\Services\Deployment\DatabaseDeploymentReleaseStateRepository';

        $this->assertTrue(
            class_exists($class),
            'Concrete database release-state repository is required.'
        );

        $reflection = new ReflectionClass($class);

        $this->assertTrue(
            $reflection->implementsInterface(
                DeploymentReleaseStateRepositoryInterface::class
            )
        );
    }

    public function test_database_repository_requires_transition_policy_dependency(): void
    {
        $class =
            'App\Services\Deployment\DatabaseDeploymentReleaseStateRepository';

        $this->assertTrue(
            class_exists($class),
            'Concrete database release-state repository is required.'
        );

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull(
            $constructor,
            'Repository constructor is required.'
        );

        $hasPolicy = false;

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (
                $type instanceof ReflectionNamedType &&
                $type->getName() ===
                    DeploymentStateTransitionPolicy::class
            ) {
                $hasPolicy = true;
                break;
            }
        }

        $this->assertTrue(
            $hasPolicy,
            'Repository must depend on DeploymentStateTransitionPolicy.'
        );
    }

    public function test_repository_source_routes_mutations_through_policy(): void
    {
        $path =
            dirname(__DIR__, 3) .
            '/app/Services/Deployment/' .
            'DatabaseDeploymentReleaseStateRepository.php';

        $this->assertFileExists(
            $path,
            'Concrete database release-state repository is required.'
        );

        $source = file_get_contents($path);

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'DeploymentStateTransitionPolicy',
            $source
        );

        $this->assertStringContainsString(
            'canTransition',
            $source
        );
    }
}
