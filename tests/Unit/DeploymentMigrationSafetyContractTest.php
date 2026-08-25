<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentMigrationManager;
use App\Services\Deployment\DeploymentMigrationStateRepositoryInterface;
use App\Services\Deployment\DeploymentPackageManifest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DeploymentMigrationSafetyContractTest extends TestCase
{
    public function test_migration_manager_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentMigrationManager::class),
            'DeploymentMigrationManager is not implemented.'
        );
    }

    public function test_migration_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentMigrationResult::class
            ),
            'DeploymentMigrationResult is not implemented.'
        );
    }

    public function test_migration_state_repository_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                DeploymentMigrationStateRepositoryInterface::class
            ),
            'DeploymentMigrationStateRepositoryInterface is not implemented.'
        );
    }

    public function test_manager_exposes_execute_method(): void
    {
        if (!class_exists(DeploymentMigrationManager::class)) {
            $this->fail(
                'DeploymentMigrationManager is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists(
                DeploymentMigrationManager::class,
                'execute'
            )
        );
    }

    public function test_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentMigrationResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentMigrationResult is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'applied',
            'skipped',
            'failed',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentMigrationResult::{$method}() is required."
            );
        }
    }

    public function test_package_without_migrations_is_valid(): void
    {
        $this->requireContract();

        $staging =
            $this->staging('none');

        $manifest =
            $this->manifest([]);

        $manager =
            new DeploymentMigrationManager(
                $this->repository(),
                $this->executor([])
            );

        $result =
            $manager->execute(
                101,
                $staging,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertSame(
            [],
            $result->applied()
        );
    }

    public function test_missing_migration_payload_is_rejected(): void
    {
        $this->requireContract();

        $name =
            '2026_08_24_120000_missing.php';

        $manifest =
            $this->manifest([
                $this->migration(
                    $name,
                    hash('sha256', 'EXPECTED')
                ),
            ]);

        $manager =
            new DeploymentMigrationManager(
                $this->repository(),
                $this->executor([])
            );

        $result =
            $manager->execute(
                101,
                $this->staging('missing'),
                $manifest
            );

        $this->assertFalse(
            $result->isValid()
        );
    }

    public function test_checksum_mismatch_is_rejected_before_execution(): void
    {
        $this->requireContract();

        $name =
            '2026_08_24_120100_checksum.php';

        $staging =
            $this->staging('checksum');

        $this->writeMigration(
            $staging,
            $name,
            '<?php return "ACTUAL";'
        );

        $executed = [];

        $manager =
            new DeploymentMigrationManager(
                $this->repository(),
                $this->executor(
                    $executed
                )
            );

        $manifest =
            $this->manifest([
                $this->migration(
                    $name,
                    hash(
                        'sha256',
                        '<?php return "EXPECTED";'
                    )
                ),
            ]);

        $result =
            $manager->execute(
                101,
                $staging,
                $manifest
            );

        $this->assertFalse(
            $result->isValid()
        );
    }

    public function test_already_applied_migration_is_skipped(): void
    {
        $this->requireContract();

        $name =
            '2026_08_24_120200_applied.php';

        $content =
            '<?php return true;';

        $staging =
            $this->staging('applied');

        $this->writeMigration(
            $staging,
            $name,
            $content
        );

        $repository =
            $this->repository([
                $name,
            ]);

        $manager =
            new DeploymentMigrationManager(
                $repository,
                $this->executor([])
            );

        $manifest =
            $this->manifest([
                $this->migration(
                    $name,
                    hash('sha256', $content)
                ),
            ]);

        $result =
            $manager->execute(
                101,
                $staging,
                $manifest
            );

        $this->assertTrue(
            $result->isValid()
        );

        $this->assertSame(
            [$name],
            $result->skipped()
        );

        $this->assertSame(
            [],
            $result->applied()
        );
    }

    public function test_migrations_execute_in_manifest_order(): void
    {
        $this->requireContract();

        $names = [
            '2026_08_24_120300_first.php',
            '2026_08_24_120400_second.php',
            '2026_08_24_120500_third.php',
        ];

        $staging =
            $this->staging('order');

        $manifestRows = [];

        foreach ($names as $name) {
            $content =
                '<?php return "'.$name.'";';

            $this->writeMigration(
                $staging,
                $name,
                $content
            );

            $manifestRows[] =
                $this->migration(
                    $name,
                    hash('sha256', $content)
                );
        }

        $executed = [];

        $manager =
            new DeploymentMigrationManager(
                $this->repository(),
                $this->executorByReference(
                    $executed
                )
            );

        $result =
            $manager->execute(
                101,
                $staging,
                $this->manifest(
                    $manifestRows
                )
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertSame(
            $names,
            $executed
        );

        $this->assertSame(
            $names,
            $result->applied()
        );
    }

    public function test_failure_stops_all_later_migrations(): void
    {
        $this->requireContract();

        $names = [
            '2026_08_24_120600_first.php',
            '2026_08_24_120700_fail.php',
            '2026_08_24_120800_never.php',
        ];

        $staging =
            $this->staging('failure');

        $rows = [];

        foreach ($names as $name) {
            $content =
                '<?php return "'.$name.'";';

            $this->writeMigration(
                $staging,
                $name,
                $content
            );

            $rows[] =
                $this->migration(
                    $name,
                    hash('sha256', $content)
                );
        }

        $executed = [];

        $manager =
            new DeploymentMigrationManager(
                $this->repository(),
                $this->executorByReference(
                    $executed,
                    $names[1]
                )
            );

        $result =
            $manager->execute(
                101,
                $staging,
                $this->manifest($rows)
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            [
                $names[0],
                $names[1],
            ],
            $executed
        );

        $this->assertSame(
            [$names[0]],
            $result->applied()
        );

        $this->assertSame(
            $names[1],
            $result->failed()
        );

        $this->assertNotContains(
            $names[2],
            $executed
        );
    }


    private function requireContract(): void
    {
        if (!class_exists(DeploymentMigrationManager::class)) {
            $this->fail(
                'DeploymentMigrationManager is not implemented.'
            );
        }
    }


    private function manifest(
        array $migrations
    ): DeploymentPackageManifest {
        return new DeploymentPackageManifest(
            'BGS-MIGRATION-001',
            '1.0.0',
            'module',
            'test',
            null,
            [],
            $migrations
        );
    }


    private function migration(
        string $name,
        string $sha
    ): array {
        return [
            'migration_name' => $name,
            'relative_path' =>
                'database/migrations/'.$name,
            'sha256' => $sha,
        ];
    }


    private function staging(
        string $suffix
    ): string {
        $root =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-migration-staging-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $root
            .DIRECTORY_SEPARATOR
            .'payload'
            .DIRECTORY_SEPARATOR
            .'database'
            .DIRECTORY_SEPARATOR
            .'migrations',
            0777,
            true
        );

        return $root;
    }


    private function writeMigration(
        string $staging,
        string $name,
        string $content
    ): void {
        file_put_contents(
            $staging
            .DIRECTORY_SEPARATOR
            .'payload'
            .DIRECTORY_SEPARATOR
            .'database'
            .DIRECTORY_SEPARATOR
            .'migrations'
            .DIRECTORY_SEPARATOR
            .$name,
            $content
        );
    }


    private function repository(
        array $applied = []
    ): DeploymentMigrationStateRepositoryInterface {
        return new class(
            $applied
        ) implements DeploymentMigrationStateRepositoryInterface {

            private array $applied;

            public function __construct(
                array $applied
            ) {
                $this->applied =
                    array_fill_keys(
                        $applied,
                        true
                    );
            }

            public function isApplied(
                int $applicationReleaseId,
                string $migrationName
            ): bool {
                return isset(
                    $this->applied[$migrationName]
                );
            }

            public function markApplied(
                int $applicationReleaseId,
                string $migrationName,
                string $relativePath,
                string $sha256
            ): void {
                $this->applied[
                    $migrationName
                ] = true;
            }
        };
    }


    private function executor(
        array $executed
    ): callable {
        return static function (
            string $migrationName,
            string $migrationPath
        ) use (&$executed): void {
            $executed[] =
                $migrationName;
        };
    }


    private function executorByReference(
        array &$executed,
        ?string $failOn = null
    ): callable {
        return static function (
            string $migrationName,
            string $migrationPath
        ) use (
            &$executed,
            $failOn
        ): void {

            $executed[] =
                $migrationName;

            if (
                $failOn !== null
                && $migrationName === $failOn
            ) {
                throw new RuntimeException(
                    'Injected migration failure.'
                );
            }
        };
    }
}
