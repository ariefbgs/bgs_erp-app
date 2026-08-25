<?php

namespace Tests\Feature\Deployment;

use App\Http\Controllers\DeploymentController;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;
use ZipArchive;

final class DeploymentPackagePrivateStagingContractTest extends TestCase
{
    use DatabaseTransactions;

    private string $packageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->packageRoot =
            storage_path('app/deployment/packages');

        File::deleteDirectory(
            storage_path('app/deployment')
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(
            storage_path('app/deployment')
        );

        parent::tearDown();
    }

    public function test_private_package_root_is_not_public_storage(): void
    {
        $packageRoot = $this->normalize(
            (string) config('deployment.paths.packages')
        );

        $publicStorage = $this->normalize(
            storage_path('app/public')
        );

        $publicRoot = $this->normalize(
            public_path()
        );

        $this->assertFalse(
            str_starts_with(
                $packageRoot,
                $publicStorage . '/'
            )
        );

        $this->assertFalse(
            str_starts_with(
                $packageRoot,
                $publicRoot . '/'
            )
        );
    }

    public function test_upload_controller_has_package_persistence_dependency(): void
    {
        $reflection =
            new \ReflectionClass(
                DeploymentController::class
            );

        $constructor =
            $reflection->getConstructor();

        $this->assertNotNull(
            $constructor,
            'DeploymentController must receive a dedicated package persistence service.'
        );

        $this->assertGreaterThanOrEqual(
            1,
            count($constructor->getParameters()),
            'DeploymentController constructor must expose package persistence dependency.'
        );
    }

    public function test_upload_does_not_use_default_or_public_storage_api(): void
    {
        $reflection =
            new ReflectionMethod(
                DeploymentController::class,
                'upload'
            );

        $source = file(
            $reflection->getFileName()
        );

        $body = implode(
            '',
            array_slice(
                $source,
                $reflection->getStartLine() - 1,
                $reflection->getEndLine()
                    - $reflection->getStartLine()
                    + 1
            )
        );

        $this->assertStringNotContainsString(
            "Storage::disk('public')",
            $body
        );

        $this->assertStringNotContainsString(
            'Storage::disk("public")',
            $body
        );

        $this->assertStringNotContainsString(
            '->store(',
            $body,
            'Deployment upload must not rely on Laravel default filesystem disk.'
        );
    }

    public function test_upload_persists_package_in_private_package_root(): void
    {
        $file = $this->validPackage('customer-supplied-name.zip');

        $response =
            $this
                ->withoutMiddleware()
                ->postJson(
                    route('deployment.upload'),
                    ['package' => $file]
                );

        $response->assertSuccessful();

        $this->assertDirectoryExists(
            $this->packageRoot
        );

        $this->assertDirectoryExists(
            $this->packageRoot,
            'Deployment upload must persist the package into the private package root.'
        );

        $files =
            File::files(
                $this->packageRoot
            );

        $this->assertCount(
            1,
            $files,
            'Exactly one persisted package is expected.'
        );
    }

    public function test_physical_filename_is_server_generated(): void
    {
        $original =
            'customer-controlled-release-name.zip';

        $file = $this->validPackage($original);

        $this
            ->withoutMiddleware()
            ->postJson(
                route('deployment.upload'),
                ['package' => $file]
            )
            ->assertSuccessful();

        $this->assertDirectoryExists(
            $this->packageRoot,
            'Deployment upload must persist the package into the private package root.'
        );

        $files =
            File::files(
                $this->packageRoot
            );

        $this->assertCount(1, $files);

        $physicalName =
            $files[0]->getFilename();

        $this->assertNotSame(
            $original,
            $physicalName
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+\.zip$/',
            $physicalName
        );
    }

    public function test_same_original_filename_cannot_collide(): void
    {
        foreach ([1, 2] as $iteration) {
            $file = $this->validPackage('same-name.zip');

            $this
                ->withoutMiddleware()
                ->postJson(
                    route('deployment.upload'),
                    ['package' => $file]
                )
                ->assertSuccessful();
        }

        $this->assertDirectoryExists(
            $this->packageRoot,
            'Deployment upload must persist the package into the private package root.'
        );

        $files =
            File::files(
                $this->packageRoot
            );

        $this->assertCount(2, $files);

        $names =
            array_map(
                fn ($file) =>
                    $file->getFilename(),
                $files
            );

        $this->assertCount(
            2,
            array_unique($names)
        );
    }

    public function test_persisted_package_has_sha256_identity(): void
    {
        $file = $this->validPackage('checksum-source.zip');
        $expected = hash_file('sha256', $file->getPathname());

        $response =
            $this
                ->withoutMiddleware()
                ->postJson(
                    route('deployment.upload'),
                    ['package' => $file]
                );

        $response->assertSuccessful();

        $this->assertDirectoryExists(
            $this->packageRoot,
            'Deployment upload must persist the package into the private package root.'
        );

        $files =
            File::files(
                $this->packageRoot
            );

        $this->assertCount(1, $files);

        $this->assertSame(
            $expected,
            hash_file(
                'sha256',
                $files[0]->getPathname()
            )
        );
    }

    public function test_upload_does_not_create_staging_or_recovery_runtime(): void
    {
        $file = $this->validPackage('intake-only.zip');

        $this
            ->withoutMiddleware()
            ->postJson(
                route('deployment.upload'),
                ['package' => $file]
            )
            ->assertSuccessful();

        $this->assertDirectoryDoesNotExist(
            storage_path('app/deployment/staging')
        );

        $this->assertDirectoryDoesNotExist(
            storage_path('app/deployment/recovery')
        );
    }

    private function normalize(string $path): string
    {
        return rtrim(
            str_replace('\\', '/', $path),
            '/'
        );
    }

    private function validPackage(string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'bgs-deployment-');

        $zip = new ZipArchive();
        $this->assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                === true
        );

        $zip->addFromString('manifest.json', json_encode([
            'release_id' => 'BGS-STAGING-' . strtoupper(bin2hex(random_bytes(8))),
            'version' => '1.0.0-test',
            'scope' => 'application',
            'files' => [],
            'migrations' => [],
        ], JSON_THROW_ON_ERROR));
        $zip->addEmptyDir('payload');
        $zip->close();

        return new UploadedFile(
            $path,
            $originalName,
            'application/zip',
            null,
            true
        );
    }
}
