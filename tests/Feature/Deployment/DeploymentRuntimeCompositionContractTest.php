<?php

namespace Tests\Feature\Deployment;

use App\Services\Deployment\DatabaseDeploymentExecutionContextProvider;
use App\Services\Deployment\DatabaseDeploymentHistoryRepository;
use App\Services\Deployment\DatabaseDeploymentMigrationStateRepository;
use App\Services\Deployment\DatabaseDeploymentReleaseStateRepository;
use App\Services\Deployment\DeploymentExecutionContextProviderInterface;
use App\Services\Deployment\DeploymentHistoryRepositoryInterface;
use App\Services\Deployment\DeploymentMigrationManager;
use App\Services\Deployment\DeploymentMigrationStateRepositoryInterface;
use App\Services\Deployment\DeploymentOrchestrator;
use App\Services\Deployment\DeploymentReleaseStateRepositoryInterface;
use Tests\TestCase;

final class DeploymentRuntimeCompositionContractTest extends TestCase
{
    public function test_deployment_interfaces_are_bound_to_production_implementations(): void
    {
        $this->assertInstanceOf(
            DatabaseDeploymentExecutionContextProvider::class,
            $this->app->make(
                DeploymentExecutionContextProviderInterface::class
            )
        );

        $this->assertInstanceOf(
            DatabaseDeploymentReleaseStateRepository::class,
            $this->app->make(
                DeploymentReleaseStateRepositoryInterface::class
            )
        );

        $this->assertInstanceOf(
            DatabaseDeploymentHistoryRepository::class,
            $this->app->make(
                DeploymentHistoryRepositoryInterface::class
            )
        );

        $this->assertInstanceOf(
            DatabaseDeploymentMigrationStateRepository::class,
            $this->app->make(
                DeploymentMigrationStateRepositoryInterface::class
            )
        );
    }

    public function test_migration_manager_is_resolvable_from_real_container(): void
    {
        $manager = $this->app->make(
            DeploymentMigrationManager::class
        );

        $this->assertInstanceOf(
            DeploymentMigrationManager::class,
            $manager
        );
    }

    public function test_orchestrator_is_resolvable_from_real_container(): void
    {
        $orchestrator = $this->app->make(
            DeploymentOrchestrator::class
        );

        $this->assertInstanceOf(
            DeploymentOrchestrator::class,
            $orchestrator
        );
    }
}