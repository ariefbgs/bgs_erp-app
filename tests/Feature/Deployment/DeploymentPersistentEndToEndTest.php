<?php

namespace Tests\Feature\Deployment;

use App\Services\Deployment\DatabaseDeploymentExecutionContextProvider;
use App\Services\Deployment\DatabaseDeploymentHistoryRepository;
use App\Services\Deployment\DatabaseDeploymentMigrationStateRepository;
use App\Services\Deployment\DatabaseDeploymentReleaseStateRepository;
use App\Services\Deployment\DeploymentBackupManager;
use App\Services\Deployment\DeploymentFileMutator;
use App\Services\Deployment\DeploymentMigrationManager;
use App\Services\Deployment\DeploymentOrchestrator;
use App\Services\Deployment\DeploymentPackageProcessor;
use App\Services\Deployment\DeploymentStateTransitionPolicy;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

final class DeploymentPersistentEndToEndTest extends TestCase
{
    private ?string $sandboxRoot = null;

    protected function setUp(): void
    {
        parent::setUp();

        $database =
            DB::connection()->getDatabaseName();

        $this->assertSame(
            'erp_app_testing',
            $database,
            'Persistent deployment E2E must never run outside erp_app_testing.'
        );

        DB::table('deployment_migrations')->delete();
        DB::table('deployment_files')->delete();
        DB::table('deployment_history')->delete();
        DB::table('application_releases')->delete();

        $this->sandboxRoot =
            sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'bgs-persistent-e2e-'
            . uniqid('', true);

        mkdir(
            $this->sandboxRoot,
            0777,
            true
        );
    }

    protected function tearDown(): void
    {
        if ($this->sandboxRoot !== null) {
            $this->removeDirectory(
                $this->sandboxRoot
            );
        }

        DB::table('deployment_migrations')->delete();
        DB::table('deployment_files')->delete();
        DB::table('deployment_history')->delete();
        DB::table('application_releases')->delete();

        parent::tearDown();
    }

    public function test_persistent_end_to_end_install(): void
    {
        $applicationRoot =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'application';

        $packageRoot =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'packages';

        $stagingRoot =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'staging';

        $recoveryRoot =
            $this->sandboxRoot
            . DIRECTORY_SEPARATOR
            . 'recovery';

        mkdir(
            $applicationRoot,
            0777,
            true
        );

        mkdir(
            $packageRoot,
            0777,
            true
        );

        config([
            'deployment.application_root'
                => $applicationRoot,

            'deployment.paths.packages'
                => $packageRoot,

            'deployment.paths.staging'
                => $stagingRoot,

            'deployment.paths.recovery'
                => $recoveryRoot,
        ]);


        /*
        |--------------------------------------------------------------------------
        | 1. CREATE RELEASE USING ACTUAL PRODUCTION SCHEMA
        |--------------------------------------------------------------------------
        */

        $manifestReleaseId =
            'BGS-PERSISTENT-E2E-' . uniqid();

        $releaseId =
            DB::table('application_releases')
                ->insertGetId([
                    'release_uuid'
                        => (string) \Illuminate\Support\Str::uuid(),

                    'release_id'
                        => $manifestReleaseId,

                    'name'
                        => 'BGS Persistent E2E Release',

                    'scope'
                        => 'application',

                    'version'
                        => '1.0.0-e2e',

                    'package_filename'
                        => 'persistent-e2e.zip',

                    'package_sha256'
                        => str_repeat('0', 64),

                    'status'
                        => 'uploaded',

                    'validated_at'
                        => null,

                    'installed_at'
                        => null,

                    'rolled_back_at'
                        => null,

                    'created_by'
                        => null,

                    'installed_by'
                        => null,

                    'rolled_back_by'
                        => null,

                    'created_at'
                        => now(),

                    'updated_at'
                        => now(),
                ]);


        /*
        |--------------------------------------------------------------------------
        | 2. BUILD REAL PACKAGE
        |--------------------------------------------------------------------------
        */

        $packagePath =
            $packageRoot
            . DIRECTORY_SEPARATOR
            . 'persistent-e2e.zip';

        $fileRelative =
            'app/PersistentInstalled.php';

        $fileContent =
            '<?php return "persistent-e2e-installed";';

        $migrationName =
            '2026_08_24_140000_persistent_e2e.php';

        $migrationRelative =
            'database/migrations/'
            . $migrationName;

        $migrationContent =
            '<?php return true;';

        $manifest = [
            'release_id' => $manifestReleaseId,
            'version' => '1.0.0-e2e',
            'scope' => 'application',

            'files' => [
                [
                    'operation' => 'add',
                    'relative_path' => $fileRelative,
                    'sha256' =>
                        hash(
                            'sha256',
                            $fileContent
                        ),
                ],
            ],

            'migrations' => [
                [
                    'migration_name'
                        => $migrationName,

                    'relative_path'
                        => $migrationRelative,

                    'sha256'
                        => hash(
                            'sha256',
                            $migrationContent
                        ),
                ],
            ],
        ];

        $zip =
            new ZipArchive();

        $open =
            $zip->open(
                $packagePath,
                ZipArchive::CREATE
                | ZipArchive::OVERWRITE
            );

        if ($open !== true) {
            throw new RuntimeException(
                'Unable to create persistent E2E package.'
            );
        }

        $zip->addFromString(
            'manifest.json',
            json_encode(
                $manifest,
                JSON_THROW_ON_ERROR
            )
        );

        $zip->addFromString(
            'payload/' . $fileRelative,
            $fileContent
        );

        $zip->addFromString(
            'payload/' . $migrationRelative,
            $migrationContent
        );

        $zip->close();


        /*
        |--------------------------------------------------------------------------
        | 3. UPDATE REAL PACKAGE METADATA
        |--------------------------------------------------------------------------
        */

        DB::table('application_releases')
            ->where('id', $releaseId)
            ->update([
                'package_sha256'
                    => hash_file(
                        'sha256',
                        $packagePath
                    ),


                'updated_at'
                    => now(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | 4. CONCRETE PRODUCTION REPOSITORIES
        |--------------------------------------------------------------------------
        */

        $transitionPolicy =
            new DeploymentStateTransitionPolicy();

        $releaseRepository =
            new DatabaseDeploymentReleaseStateRepository(
                $transitionPolicy
            );

        $historyRepository =
            new DatabaseDeploymentHistoryRepository();

        $migrationRepository =
            new DatabaseDeploymentMigrationStateRepository();


        /*
        |--------------------------------------------------------------------------
        | 5. CONTROLLED TEST MIGRATION EXECUTOR
        |--------------------------------------------------------------------------
        |
        | MigrationManager is real.
        | Persistence is real.
        | The executor is intentionally a no-op because this test verifies
        | deployment orchestration and migration-state persistence, not
        | Laravel's schema migration engine itself.
        |--------------------------------------------------------------------------
        */

        $migrationManager =
            new DeploymentMigrationManager(
                $migrationRepository,
                static function (
                    string $migrationName,
                    string $migrationPath
                ): void {
                    if (!is_file($migrationPath)) {
                        throw new RuntimeException(
                            'Migration payload missing.'
                        );
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | 6. REAL EXECUTION CONTEXT PROVIDER
        |--------------------------------------------------------------------------
        */

        $contextProvider =
            new DatabaseDeploymentExecutionContextProvider();


        /*
        |--------------------------------------------------------------------------
        | 7. REAL ORCHESTRATOR
        |--------------------------------------------------------------------------
        */

        $orchestrator =
            new DeploymentOrchestrator(
                $contextProvider,
                $releaseRepository,
                $historyRepository,
                new DeploymentPackageProcessor(),
                new DeploymentBackupManager(),
                new DeploymentFileMutator(),
                $migrationManager
            );


        /*
        |--------------------------------------------------------------------------
        | 8. INSTALL
        |--------------------------------------------------------------------------
        */

        $result =
            $orchestrator->install(
                (int) $releaseId
            );


        /*
        |--------------------------------------------------------------------------
        | 9. RESULT CONTRACT
        |--------------------------------------------------------------------------
        */

        $this->assertTrue(
            $result->isSuccessful(),
            implode(
                ' | ',
                $result->errors()
            )
        );

        $this->assertSame(
            'installed',
            $result->releaseStatus()
        );


        /*
        |--------------------------------------------------------------------------
        | 10. RELEASE PERSISTENCE
        |--------------------------------------------------------------------------
        */

        $release =
            DB::table('application_releases')
                ->where(
                    'id',
                    $releaseId
                )
                ->first();

        $this->assertNotNull($release);

        $this->assertSame(
            'installed',
            $release->status
        );

        $this->assertNotNull(
            $release->validated_at
        );

        $this->assertNotNull(
            $release->installed_at
        );


        /*
        |--------------------------------------------------------------------------
        | 11. HISTORY PERSISTENCE
        |--------------------------------------------------------------------------
        */

        $history =
            DB::table('deployment_history')
                ->where(
                    'application_release_id',
                    $releaseId
                )
                ->first();

        $this->assertNotNull($history);

        $this->assertSame(
            'install',
            $history->action
        );

        $this->assertSame(
            'success',
            $history->status
        );

        $this->assertNotNull(
            $history->started_at
        );

        $this->assertNotNull(
            $history->completed_at
        );


        /*
        |--------------------------------------------------------------------------
        | 12. FILESYSTEM MUTATION
        |--------------------------------------------------------------------------
        */

        $installedPath =
            $applicationRoot
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'PersistentInstalled.php';

        $this->assertFileExists(
            $installedPath
        );

        $this->assertSame(
            $fileContent,
            file_get_contents(
                $installedPath
            )
        );


        /*
        |--------------------------------------------------------------------------
        | 13. MIGRATION PERSISTENCE
        |--------------------------------------------------------------------------
        */

        $migration =
            DB::table('deployment_migrations')
                ->where(
                    'application_release_id',
                    $releaseId
                )
                ->where(
                    'migration_name',
                    $migrationName
                )
                ->first();

        $this->assertNotNull(
            $migration
        );

        $this->assertSame(
            $migrationRelative,
            $migration->relative_path
        );

        $this->assertSame(
            hash(
                'sha256',
                $migrationContent
            ),
            $migration->sha256
        );

        $this->assertNotNull(
            $migration->executed_at
        );


        /*
        |--------------------------------------------------------------------------
        | 14. PROVE FILESYSTEM CONTAINMENT
        |--------------------------------------------------------------------------
        */

        $projectRoot =
            realpath(
                base_path()
            );

        $sandbox =
            realpath(
                $this->sandboxRoot
            );

        $installed =
            realpath(
                $installedPath
            );

        $this->assertNotFalse($sandbox);
        $this->assertNotFalse($installed);

        $this->assertStringStartsWith(
            $sandbox,
            $installed
        );

        $this->assertNotSame(
            $projectRoot
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'PersistentInstalled.php',
            $installed
        );
    }

    private function removeDirectory(
        string $path
    ): void {
        if (!is_dir($path)) {
            return;
        }

        $items =
            scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $target =
                $path
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($target)) {
                $this->removeDirectory(
                    $target
                );
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
