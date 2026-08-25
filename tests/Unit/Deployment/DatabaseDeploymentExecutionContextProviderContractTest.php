<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DeploymentExecutionContextProviderInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DatabaseDeploymentExecutionContextProviderContractTest
    extends TestCase
{
    private string $class =
        'App\Services\Deployment\DatabaseDeploymentExecutionContextProvider';

    public function test_dedicated_deployment_configuration_exists(): void
    {
        $path =
            dirname(__DIR__, 3) .
            '/config/deployment.php';

        $this->assertFileExists(
            $path,
            'Dedicated config/deployment.php is required.'
        );
    }

    public function test_database_execution_context_provider_exists(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'DatabaseDeploymentExecutionContextProvider is required.'
        );
    }

    public function test_provider_implements_canonical_interface(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'DatabaseDeploymentExecutionContextProvider is required.'
        );

        $reflection =
            new ReflectionClass($this->class);

        $this->assertTrue(
            $reflection->implementsInterface(
                DeploymentExecutionContextProviderInterface::class
            ),
            'Concrete provider must implement DeploymentExecutionContextProviderInterface.'
        );
    }

    public function test_provider_exposes_for_release(): void
    {
        $this->assertTrue(
            class_exists($this->class),
            'DatabaseDeploymentExecutionContextProvider is required.'
        );

        $reflection =
            new ReflectionClass($this->class);

        $this->assertTrue(
            $reflection->hasMethod('forRelease')
        );
    }

    public function test_provider_uses_application_releases_as_release_authority(): void
    {
        $source = $this->providerSource();

        $this->assertStringContainsString(
            'application_releases',
            $source,
            'Provider must resolve release metadata from application_releases.'
        );
    }

    public function test_provider_uses_dedicated_deployment_configuration(): void
    {
        $source = $this->providerSource();

        $this->assertStringContainsString(
            "deployment.",
            $source,
            'Provider must use dedicated deployment configuration.'
        );
    }

    public function test_provider_does_not_use_public_filesystem_disk(): void
    {
        $source = $this->providerSource();

        $this->assertStringNotContainsString(
            "disk('public')",
            $source
        );

        $this->assertStringNotContainsString(
            'filesystems.default',
            $source
        );
    }

    public function test_provider_contains_no_hard_coded_windows_project_path(): void
    {
        $source = $this->providerSource();

        $this->assertStringNotContainsString(
            'D:\\projects\\',
            $source
        );

        $this->assertStringNotContainsString(
            'D:/projects/',
            $source
        );
    }

    private function providerSource(): string
    {
        $this->assertTrue(
            class_exists($this->class),
            'DatabaseDeploymentExecutionContextProvider is required.'
        );

        $reflection =
            new ReflectionClass($this->class);

        $file =
            $reflection->getFileName();

        $this->assertIsString($file);

        $source =
            file_get_contents($file);

        $this->assertIsString($source);

        return $source;
    }
}