<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;

final class DeploymentOrchestratorContractTest extends TestCase
{
    public function test_orchestrator_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentOrchestrator::class
            ),
            'DeploymentOrchestrator is not implemented.'
        );
    }

    public function test_orchestrator_exposes_install_method(): void
    {
        $class =
            \App\Services\Deployment\DeploymentOrchestrator::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentOrchestrator is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'install'),
            'DeploymentOrchestrator::install() is required.'
        );
    }

    public function test_orchestration_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentOrchestrationResult::class
            ),
            'DeploymentOrchestrationResult is not implemented.'
        );
    }

    public function test_orchestration_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentOrchestrationResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentOrchestrationResult is not implemented.'
            );
        }

        foreach ([
            'isSuccessful',
            'errors',
            'releaseStatus',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentOrchestrationResult::{$method}() is required."
            );
        }
    }

    public function test_release_state_repository_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                \App\Services\Deployment\DeploymentReleaseStateRepositoryInterface::class
            ),
            'DeploymentReleaseStateRepositoryInterface is not implemented.'
        );
    }

    public function test_release_repository_exposes_required_state_operations(): void
    {
        $class =
            \App\Services\Deployment\DeploymentReleaseStateRepositoryInterface::class;

        if (!interface_exists($class)) {
            $this->fail(
                'DeploymentReleaseStateRepositoryInterface is not implemented.'
            );
        }

        foreach ([
            'status',
            'markValidated',
            'markInstalled',
            'markFailed',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "{$class}::{$method}() is required."
            );
        }
    }

    public function test_execution_history_repository_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                \App\Services\Deployment\DeploymentHistoryRepositoryInterface::class
            ),
            'DeploymentHistoryRepositoryInterface is not implemented.'
        );
    }

    public function test_execution_history_contract_supports_started_success_and_failed(): void
    {
        $class =
            \App\Services\Deployment\DeploymentHistoryRepositoryInterface::class;

        if (!interface_exists($class)) {
            $this->fail(
                'DeploymentHistoryRepositoryInterface is not implemented.'
            );
        }

        foreach ([
            'start',
            'markSuccess',
            'markFailed',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "{$class}::{$method}() is required."
            );
        }
    }
}