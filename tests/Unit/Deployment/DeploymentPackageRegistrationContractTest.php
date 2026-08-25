<?php

namespace Tests\Unit\Deployment;

use PHPUnit\Framework\TestCase;

final class DeploymentPackageRegistrationContractTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectRoot = dirname(__DIR__, 3);
    }

    public function test_registration_service_exists(): void
    {
        $this->assertFileExists(
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationService.php'
        );
    }

    public function test_registration_result_exists(): void
    {
        $this->assertFileExists(
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationResult.php'
        );
    }

    public function test_registration_service_exposes_register_method(): void
    {
        $path =
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationService.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertIsString($source);
        $this->assertMatchesRegularExpression(
            '/public\s+function\s+register\s*\(/',
            $source
        );
    }

    public function test_registration_result_exposes_safe_contract(): void
    {
        $path =
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationResult.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertIsString($source);

        $this->assertMatchesRegularExpression(
            '/function\s+isSuccess\s*\(/',
            $source
        );

        $this->assertMatchesRegularExpression(
            '/function\s+releaseId\s*\(/',
            $source
        );
    }

    public function test_registration_service_targets_application_release_persistence(): void
    {
        $path =
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationService.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertIsString($source);

        $this->assertStringContainsString(
            'ApplicationRelease',
            $source
        );
    }

    public function test_registration_contract_preserves_package_identity(): void
    {
        $path =
            $this->projectRoot .
            '/app/Services/Deployment/DeploymentPackageRegistrationService.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);

        $this->assertIsString($source);

        $this->assertStringContainsString('sha256', $source);
        $this->assertStringContainsString('package_filename', $source);
    }
}
