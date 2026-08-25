<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DatabaseDeploymentExecutionContextProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

final class DatabaseDeploymentExecutionContextProviderBehaviorTest
    extends TestCase
{
    private DatabaseDeploymentExecutionContextProvider $provider;

    private string $packageRoot;
    private string $stagingRoot;
    private string $recoveryRoot;
    private string $applicationRoot;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('application_releases')->delete();

        $base =
            storage_path(
                'framework/testing/deployment-context'
            );

        $this->packageRoot =
            $base . DIRECTORY_SEPARATOR . 'packages';

        $this->stagingRoot =
            $base . DIRECTORY_SEPARATOR . 'staging';

        $this->recoveryRoot =
            $base . DIRECTORY_SEPARATOR . 'recovery';

        $this->applicationRoot =
            base_path();

        config([
            'deployment.application_root' =>
                $this->applicationRoot,

            'deployment.paths.packages' =>
                $this->packageRoot,

            'deployment.paths.staging' =>
                $this->stagingRoot,

            'deployment.paths.recovery' =>
                $this->recoveryRoot,
        ]);

        $this->provider =
            new DatabaseDeploymentExecutionContextProvider();
    }

    protected function tearDown(): void
    {
        DB::table('application_releases')->delete();

        parent::tearDown();
    }

    public function test_valid_release_resolves_canonical_context(): void
    {
        $id = $this->release('release-001.zip');

        $context =
            $this->provider->forRelease($id);

        $this->assertSame(
            $this->applicationRoot,
            $context->applicationRoot()
        );

        $this->assertSame(
            $this->packageRoot .
                DIRECTORY_SEPARATOR .
                'release-001.zip',
            $context->packagePath()
        );

        $this->assertSame(
            $this->stagingRoot .
                DIRECTORY_SEPARATOR .
                (string) $id,
            $context->stagingPath()
        );

        $this->assertSame(
            $this->recoveryRoot .
                DIRECTORY_SEPARATOR .
                (string) $id,
            $context->recoveryPath()
        );

        $this->assertSame(
            str_repeat('a', 64),
            $context->expectedPackageSha256()
        );

        $release = DB::table('application_releases')->find($id);

        $this->assertSame([
            'release_id' => $release->release_id,
            'version' => $release->version,
            'scope' => $release->scope,
            'module' => $release->module,
            'feature' => $release->feature,
        ], $context->expectedManifestIdentity());
    }

    public function test_nonexistent_release_fails_closed(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'was not found'
        );

        $this->provider->forRelease(999999);
    }

    public function test_zero_release_id_fails_closed(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        $this->provider->forRelease(0);
    }

    public function test_negative_release_id_fails_closed(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        $this->provider->forRelease(-10);
    }

    public function test_unix_traversal_cannot_escape_package_root(): void
    {
        $id =
            $this->release(
                '../../outside/evil.zip'
            );

        $context =
            $this->provider->forRelease($id);

        $this->assertSame(
            $this->packageRoot .
                DIRECTORY_SEPARATOR .
                'evil.zip',
            $context->packagePath()
        );

        $this->assertStringStartsWith(
            $this->packageRoot .
                DIRECTORY_SEPARATOR,
            $context->packagePath()
        );
    }

    public function test_windows_traversal_cannot_escape_package_root(): void
    {
        $id =
            $this->release(
                '..\\..\\outside\\evil.zip'
            );

        $context =
            $this->provider->forRelease($id);

        $this->assertSame(
            $this->packageRoot .
                DIRECTORY_SEPARATOR .
                'evil.zip',
            $context->packagePath()
        );

        $this->assertStringStartsWith(
            $this->packageRoot .
                DIRECTORY_SEPARATOR,
            $context->packagePath()
        );
    }

    public function test_absolute_windows_filename_is_reduced_to_basename(): void
    {
        $id =
            $this->release(
                'C:\\temp\\malicious.zip'
            );

        $context =
            $this->provider->forRelease($id);

        $this->assertSame(
            $this->packageRoot .
                DIRECTORY_SEPARATOR .
                'malicious.zip',
            $context->packagePath()
        );
    }

    public function test_releases_receive_isolated_runtime_paths(): void
    {
        $first =
            $this->release('first.zip');

        $second =
            $this->release('second.zip');

        $firstContext =
            $this->provider->forRelease($first);

        $secondContext =
            $this->provider->forRelease($second);

        $this->assertNotSame(
            $firstContext->stagingPath(),
            $secondContext->stagingPath()
        );

        $this->assertNotSame(
            $firstContext->recoveryPath(),
            $secondContext->recoveryPath()
        );
    }

    public function test_resolving_context_does_not_create_runtime_directories(): void
    {
        $id =
            $this->release('no-side-effect.zip');

        $context =
            $this->provider->forRelease($id);

        $this->assertDirectoryDoesNotExist(
            $context->stagingPath()
        );

        $this->assertDirectoryDoesNotExist(
            $context->recoveryPath()
        );
    }

    public function test_context_does_not_resolve_into_public_storage(): void
    {
        $id =
            $this->release('private.zip');

        $context =
            $this->provider->forRelease($id);

        $public =
            str_replace(
                '\\',
                '/',
                storage_path('app/public')
            );

        foreach ([
            $context->packagePath(),
            $context->stagingPath(),
            $context->recoveryPath(),
        ] as $path) {
            $normalized =
                str_replace('\\', '/', $path);

            $this->assertFalse(
                str_starts_with(
                    $normalized,
                    $public . '/'
                )
            );
        }
    }

    private function release(
        ?string $filename
    ): int {
        $identity =
            str_replace(
                '.',
                '-',
                uniqid('context-', true)
            );

        return (int) DB::table(
            'application_releases'
        )->insertGetId([
            'release_uuid' =>
                (string) \Illuminate\Support\Str::uuid(),
            'release_id' =>
                'TEST-' . $identity,
            'name' =>
                'Execution Context Test ' . $identity,
            'scope' => 'application',
            'version' => '1.0.0',
            'status' => 'uploaded',
            'package_filename' => $filename,
            'package_sha256' =>
                str_repeat('a', 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
