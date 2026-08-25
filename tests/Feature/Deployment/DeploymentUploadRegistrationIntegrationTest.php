<?php

namespace Tests\Feature\Deployment;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

final class DeploymentUploadRegistrationIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private string $sandboxRoot;
    private string $packageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $database = (string) DB::connection()->getDatabaseName();

        $this->assertSame(
            'erp_app_testing',
            $database,
            'Upload registration integration must use the isolated testing database.'
        );

        $this->assertNotSame('erp_app', $database);

        $this->sandboxRoot =
            storage_path(
                'framework/testing/deployment-upload-registration'
            );

        $this->packageRoot =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'packages';

        File::deleteDirectory(
            $this->sandboxRoot
        );

        File::ensureDirectoryExists(
            $this->packageRoot
        );

        config([
            'deployment.paths.packages'
                => $this->packageRoot,
            'deployment.paths.staging'
                => $this->sandboxRoot . DIRECTORY_SEPARATOR . 'staging',
            'deployment.paths.recovery'
                => $this->sandboxRoot . DIRECTORY_SEPARATOR . 'recovery',
            'deployment.application_root'
                => $this->sandboxRoot . DIRECTORY_SEPARATOR . 'application',
        ]);

        File::ensureDirectoryExists(
            (string) config('deployment.application_root')
        );
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(
            $this->sandboxRoot
        );

        parent::tearDown();
    }

    public function test_successful_upload_registers_canonical_application_release(): void
    {
        $releaseId =
            'BGS-UPLOAD-INTEGRATION-'
            . strtoupper(
                bin2hex(random_bytes(4))
            );

        $version =
            '1.0.0-upload-integration';

        $manifest = [
            'release_id' => $releaseId,
            'version' => $version,
            'scope' => 'application',
            'files' => [],
            'migrations' => [],
        ];

        $zipPath =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'source.zip';

        $zip =
            new ZipArchive();

        $open =
            $zip->open(
                $zipPath,
                ZipArchive::CREATE
                | ZipArchive::OVERWRITE
            );

        $this->assertTrue(
            $open === true
        );

        $zip->addFromString(
            'manifest.json',
            json_encode(
                $manifest,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        $zip->addEmptyDir('payload');

        $zip->close();

        $before =
            DB::table(
                'application_releases'
            )->count();

        $uploaded =
            new UploadedFile(
                $zipPath,
                'bgs-upload-integration.zip',
                'application/zip',
                null,
                true
            );

        $response =
            $this
                ->withoutMiddleware()
                ->post(
                    route('deployment.upload'),
                    [
                        'package'
                            => $uploaded,
                    ]
                );

        $response->assertSuccessful();

        $after =
            DB::table(
                'application_releases'
            )->count();

        $this->assertSame(
            $before + 1,
            $after,
            'Successful upload must register exactly one ApplicationRelease.'
        );

        $release =
            DB::table(
                'application_releases'
            )
            ->where(
                'release_id',
                $releaseId
            )
            ->first();

        $this->assertNotNull(
            $release,
            'Uploaded package manifest identity must be registered.'
        );

        $this->assertSame(
            'application',
            $release->scope
        );

        $this->assertSame(
            $version,
            $release->version
        );

        $this->assertSame(
            'uploaded',
            $release->status
        );

        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+\.zip$/',
            $release->package_filename
        );

        $this->assertFileExists(
            $this->packageRoot . DIRECTORY_SEPARATOR . $release->package_filename
        );

        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $release->package_sha256
        );

        $this->assertSame(
            hash_file(
                'sha256',
                $this->packageRoot
                    . DIRECTORY_SEPARATOR
                    . $release->package_filename
            ),
            $release->package_sha256
        );
    }

    public function test_duplicate_upload_fails_closed_without_orphaned_package(): void
    {
        $releaseId =
            'BGS-UPLOAD-DUPLICATE-'
            . strtoupper(bin2hex(random_bytes(4)));

        $first = $this->validUploadedPackage(
            $releaseId,
            'first-upload.zip'
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $first])
            ->assertNoContent();

        $this->assertCount(1, File::files($this->packageRoot));

        $second = $this->validUploadedPackage(
            $releaseId,
            'duplicate-upload.zip'
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $second])
            ->assertStatus(409);

        $this->assertSame(
            1,
            DB::table('application_releases')
                ->where('release_id', $releaseId)
                ->count()
        );

        $this->assertCount(
            1,
            File::files($this->packageRoot),
            'Rejected duplicate upload must not leave an orphaned ZIP.'
        );
    }

    public function test_invalid_package_is_removed_after_secure_processing_failure(): void
    {
        $before = DB::table('application_releases')->count();
        $path = $this->sandboxRoot . DIRECTORY_SEPARATOR . 'invalid.zip';
        $zip = new ZipArchive();

        $this->assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                === true
        );

        $zip->addFromString('manifest.json', '{invalid-json');
        $zip->addEmptyDir('payload');
        $zip->close();

        $uploaded = new UploadedFile(
            $path,
            'invalid-package.zip',
            'application/zip',
            null,
            true
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $uploaded])
            ->assertStatus(422);

        $this->assertSame(
            $before,
            DB::table('application_releases')->count()
        );

        $this->assertCount(0, File::files($this->packageRoot));
        $this->assertDirectoryDoesNotExist(
            (string) config('deployment.paths.staging')
        );
    }

    public function test_registered_release_can_be_installed_through_http_boundary(): void
    {
        $actor = User::query()->create([
            'name' => 'Deployment Test Actor',
            'email' => 'deployment-actor-' . bin2hex(random_bytes(5)) . '@example.test',
            'password' => 'test-password',
        ]);
        $actor->role = 'admin';
        $actor->save();

        $this->actingAs($actor);

        $releaseId =
            'BGS-HTTP-INSTALL-'
            . strtoupper(bin2hex(random_bytes(4)));

        $marker =
            (string) config('deployment.application_root')
            . DIRECTORY_SEPARATOR
            . 'untouched.txt';

        File::put($marker, 'UNCHANGED');

        $uploaded = $this->validUploadedPackage(
            $releaseId,
            'http-install.zip'
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $uploaded])
            ->assertNoContent();

        $release = DB::table('application_releases')
            ->where('release_id', $releaseId)
            ->first();

        $this->assertNotNull($release);
        $this->assertSame((int) $actor->id, (int) $release->created_by);

        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'), [
                'application_release_id' => $release->id,
            ])
            ->assertOk()
            ->assertJson([
                'release_status' => 'installed',
            ]);

        $this->assertSame(
            'installed',
            DB::table('application_releases')
                ->where('id', $release->id)
                ->value('status')
        );

        $this->assertSame(
            (int) $actor->id,
            (int) DB::table('application_releases')
                ->where('id', $release->id)
                ->value('installed_by')
        );

        $history = DB::table('deployment_history')
            ->where('application_release_id', $release->id)
            ->where('action', 'install')
            ->first();

        $this->assertNotNull($history);
        $this->assertSame('success', $history->status);
        $this->assertSame((int) $actor->id, (int) $history->performed_by);
        $this->assertSame('UNCHANGED', File::get($marker));
        $this->assertDirectoryDoesNotExist(
            (string) config('deployment.paths.staging')
                . DIRECTORY_SEPARATOR
                . $release->id,
            'Install must remove its extracted private staging payload.'
        );
    }

    public function test_install_rejects_private_zip_changed_after_registration(): void
    {
        $releaseId =
            'BGS-TAMPER-TARGET-'
            . strtoupper(bin2hex(random_bytes(4)));

        $uploaded = $this->validUploadedPackage(
            $releaseId,
            'tamper-target.zip'
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $uploaded])
            ->assertNoContent();

        $release = DB::table('application_releases')
            ->where('release_id', $releaseId)
            ->first();

        $this->assertNotNull($release);

        $replacement = $this->validUploadedPackage(
            'BGS-DIFFERENT-PACKAGE-' . strtoupper(bin2hex(random_bytes(4))),
            'replacement.zip'
        );

        File::copy(
            $replacement->getPathname(),
            $this->packageRoot
                . DIRECTORY_SEPARATOR
                . $release->package_filename
        );

        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'), [
                'application_release_id' => $release->id,
            ])
            ->assertStatus(409);

        $this->assertSame(
            'failed',
            DB::table('application_releases')
                ->where('id', $release->id)
                ->value('status')
        );

        $this->assertSame(
            'failed',
            DB::table('deployment_history')
                ->where('application_release_id', $release->id)
                ->value('status')
        );

        $this->assertDirectoryDoesNotExist(
            (string) config('deployment.paths.staging')
                . DIRECTORY_SEPARATOR
                . $release->id
        );
    }

    public function test_install_rejects_registered_metadata_changed_after_upload(): void
    {
        $releaseId =
            'BGS-METADATA-TAMPER-'
            . strtoupper(bin2hex(random_bytes(4)));

        $uploaded = $this->validUploadedPackage(
            $releaseId,
            'metadata-tamper.zip'
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $uploaded])
            ->assertNoContent();

        $release = DB::table('application_releases')
            ->where('release_id', $releaseId)
            ->first();

        $this->assertNotNull($release);

        DB::table('application_releases')
            ->where('id', $release->id)
            ->update(['version' => '9.9.9-tampered']);

        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'), [
                'application_release_id' => $release->id,
            ])
            ->assertStatus(409);

        $this->assertSame(
            'failed',
            DB::table('application_releases')
                ->where('id', $release->id)
                ->value('status')
        );

        $this->assertSame(
            'failed',
            DB::table('deployment_history')
                ->where('application_release_id', $release->id)
                ->value('status')
        );
    }

    public function test_registered_release_executes_validated_migration_through_runtime_binding(): void
    {
        $releaseId =
            'BGS-MIGRATION-RUNTIME-'
            . strtoupper(bin2hex(random_bytes(4)));
        $migrationName =
            '2026_08_25_120000_runtime_binding_noop.php';
        $migrationPath = 'database/migrations/' . $migrationName;
        $migrationContent =
            '<?php return new class { public function up(): void {} };';
        $zipPath = $this->sandboxRoot . DIRECTORY_SEPARATOR . 'migration.zip';
        $zip = new ZipArchive();

        $this->assertTrue(
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                === true
        );
        $zip->addFromString('manifest.json', json_encode([
            'release_id' => $releaseId,
            'version' => '1.0.0-migration-runtime',
            'scope' => 'application',
            'files' => [],
            'migrations' => [[
                'migration_name' => $migrationName,
                'relative_path' => $migrationPath,
                'sha256' => hash('sha256', $migrationContent),
            ]],
        ], JSON_THROW_ON_ERROR));
        $zip->addFromString('payload/' . $migrationPath, $migrationContent);
        $zip->close();

        $uploaded = new UploadedFile(
            $zipPath,
            'migration-runtime.zip',
            'application/zip',
            null,
            true
        );

        $this
            ->withoutMiddleware()
            ->post(route('deployment.upload'), ['package' => $uploaded])
            ->assertNoContent();

        $release = DB::table('application_releases')
            ->where('release_id', $releaseId)
            ->first();

        $this->assertNotNull($release);

        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'), [
                'application_release_id' => $release->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('deployment_migrations', [
            'application_release_id' => $release->id,
            'migration_name' => $migrationName,
            'status' => 'executed',
        ]);
    }

    private function validUploadedPackage(
        string $releaseId,
        string $originalFilename
    ): UploadedFile {
        $path =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . bin2hex(random_bytes(4))
            . '.zip';

        $zip = new ZipArchive();

        $this->assertTrue(
            $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE)
                === true
        );

        $zip->addFromString(
            'manifest.json',
            json_encode([
                'release_id' => $releaseId,
                'version' => '1.0.0-upload-integration',
                'scope' => 'application',
                'files' => [],
                'migrations' => [],
            ], JSON_THROW_ON_ERROR)
        );
        $zip->addEmptyDir('payload');
        $zip->close();

        return new UploadedFile(
            $path,
            $originalFilename,
            'application/zip',
            null,
            true
        );
    }
}
