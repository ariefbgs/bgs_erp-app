<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DeploymentOrchestrator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DeploymentOrchestratorAtomicityContractTest extends TestCase
{
    public function test_orchestrator_requires_release_state_repository(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentReleaseStateRepositoryInterface',
            $dependencies,
            'Orchestrator must depend on the canonical release-state repository.'
        );
    }

    public function test_orchestrator_requires_execution_history_repository(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentHistoryRepositoryInterface',
            $dependencies,
            'Orchestrator must record every install attempt in deployment history.'
        );
    }

    public function test_orchestrator_requires_package_processor(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentPackageProcessor',
            $dependencies,
            'Package processing must be part of orchestration before installation.'
        );
    }

    public function test_orchestrator_requires_backup_manager(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentBackupManager',
            $dependencies,
            'Backup/recovery preparation must precede destructive mutation.'
        );
    }

    public function test_orchestrator_requires_file_mutator(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentFileMutator',
            $dependencies,
            'File mutation must be explicitly orchestrated.'
        );
    }

    public function test_orchestrator_requires_migration_manager(): void
    {
        $dependencies = $this->constructorDependencies();

        $this->assertContains(
            'App\Services\Deployment\DeploymentMigrationManager',
            $dependencies,
            'Migration execution must be explicitly orchestrated.'
        );
    }

    public function test_orchestrator_source_contains_started_history_before_terminal_success(): void
    {
        $source = $this->source();

        $start = strpos($source, '->start(');
        $success = strpos($source, '->markSuccess(');

        $this->assertNotFalse(
            $start,
            'Orchestrator must start a deployment-history attempt.'
        );

        $this->assertNotFalse(
            $success,
            'Orchestrator must explicitly mark successful history.'
        );

        $this->assertLessThan(
            $success,
            $start,
            'History STARTED must occur before SUCCESS.'
        );
    }

    public function test_orchestrator_marks_installed_before_history_success(): void
    {
        $source = $this->source();

        $installed = strpos($source, '->markInstalled(');
        $success = strpos($source, '->markSuccess(');

        $this->assertNotFalse(
            $installed,
            'Successful orchestration must mark the release installed.'
        );

        $this->assertNotFalse(
            $success,
            'Successful orchestration must mark history success.'
        );

        $this->assertLessThan(
            $success,
            $installed,
            'Release must reach installed only at the terminal success boundary.'
        );
    }

    public function test_orchestrator_contains_explicit_failure_path(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            '->markFailed(',
            $source,
            'Orchestrator must have an explicit persisted failure path.'
        );

        $this->assertStringContainsString(
            'catch',
            $source,
            'Unexpected exceptions must be converted into fail-closed orchestration.'
        );
    }

    public function test_orchestrator_does_not_use_one_global_database_transaction_as_filesystem_atomicity(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString(
            'DB::transaction',
            $source,
            'Filesystem and migration deployment must not pretend to be atomic through one DB transaction.'
        );

        $this->assertStringNotContainsString(
            'beginTransaction',
            $source,
            'Filesystem and migration deployment must use explicit compensation/recovery semantics.'
        );
    }

    private function constructorDependencies(): array
    {
        $reflection =
            new ReflectionClass(
                DeploymentOrchestrator::class
            );

        $constructor =
            $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $dependencies = [];

        foreach (
            $constructor->getParameters()
            as $parameter
        ) {
            $type = $parameter->getType();

            if (
                $type !== null
                && method_exists($type, 'getName')
            ) {
                $dependencies[] =
                    $type->getName();
            }
        }

        return $dependencies;
    }

    private function source(): string
    {
        $reflection =
            new ReflectionClass(
                DeploymentOrchestrator::class
            );

        $file =
            $reflection->getFileName();

        $this->assertIsString($file);

        $source =
            file_get_contents($file);

        $this->assertIsString($source);

        return $source;
    }
}
