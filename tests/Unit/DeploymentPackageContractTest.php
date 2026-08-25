<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeploymentPackageContractTest extends TestCase
{
    public function test_package_validator_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Services\Deployment\DeploymentPackageValidator::class),
            'DeploymentPackageValidator contract is not implemented.'
        );
    }

    public function test_package_manifest_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Services\Deployment\DeploymentPackageManifest::class),
            'DeploymentPackageManifest contract is not implemented.'
        );
    }

    public function test_package_validation_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(\App\Services\Deployment\DeploymentValidationResult::class),
            'DeploymentValidationResult contract is not implemented.'
        );
    }

    public function test_validator_exposes_validate_method(): void
    {
        $class = \App\Services\Deployment\DeploymentPackageValidator::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageValidator contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'validate'),
            'DeploymentPackageValidator::validate() is required.'
        );
    }

    public function test_manifest_exposes_release_identity(): void
    {
        $class = \App\Services\Deployment\DeploymentPackageManifest::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageManifest contract is not implemented.'
            );
        }

        foreach ([
            'releaseId',
            'version',
            'scope',
            'files',
            'migrations',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentPackageManifest::{$method}() is required."
            );
        }
    }

    public function test_validation_result_exposes_safe_result_contract(): void
    {
        $class = \App\Services\Deployment\DeploymentValidationResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentValidationResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'manifest',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentValidationResult::{$method}() is required."
            );
        }
    }
}