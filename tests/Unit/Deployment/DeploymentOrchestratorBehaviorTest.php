<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DeploymentBackupManager;
use App\Services\Deployment\DeploymentExecutionContext;
use App\Services\Deployment\DeploymentExecutionContextProviderInterface;
use App\Services\Deployment\DeploymentFileMutator;
use App\Services\Deployment\DeploymentHistoryRepositoryInterface;
use App\Services\Deployment\DeploymentMigrationManager;
use App\Services\Deployment\DeploymentMigrationStateRepositoryInterface;
use App\Services\Deployment\DeploymentOrchestrator;
use App\Services\Deployment\DeploymentPackageProcessor;
use App\Services\Deployment\DeploymentReleaseStateRepositoryInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class DeploymentOrchestratorBehaviorTest extends TestCase
{
    private array $roots = [];

    protected function tearDown(): void
    {
        foreach ($this->roots as $root) {
            $this->removeDirectory($root);
        }

        parent::tearDown();
    }

    public function test_happy_path_reaches_installed_and_history_success(): void
    {
        $fixture = $this->fixture('happy');

        $content = '<?php return "installed";';

        $relative = 'app/Installed.php';

        $zip = $this->package(
            $fixture['root'],
            [
                'release_id' => 'BGS-SANDBOX-001',
                'version' => '1.0.0',
                'scope' => 'application',
                'files' => [[
                    'operation' => 'add',
                    'relative_path' => $relative,
                    'sha256' => hash('sha256', $content),
                ]],
                'migrations' => [],
            ],
            [
                $relative => $content,
            ]
        );

        $state = $this->state('uploaded');
        $history = $this->history();

        $orchestrator = $this->orchestrator(
            101,
            $zip,
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(101);

        $this->assertTrue(
            $result->isSuccessful(),
            implode(' | ', $result->errors())
        );

        $this->assertSame(
            'installed',
            $result->releaseStatus()
        );

        $this->assertSame(
            'installed',
            $state->current
        );

        $this->assertSame(
            ['start', 'success'],
            $history->events
        );

        $installed =
            $fixture['application']
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Installed.php';

        $this->assertFileExists($installed);

        $this->assertSame(
            $content,
            file_get_contents($installed)
        );
    }

    public function test_invalid_initial_state_stops_before_history_and_execution(): void
    {
        $fixture = $this->fixture('invalid-state');

        $state = $this->state('validated');
        $history = $this->history();

        $orchestrator = $this->orchestrator(
            102,
            $fixture['root'] . DIRECTORY_SEPARATOR . 'missing.zip',
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(102);

        $this->assertFalse($result->isSuccessful());

        $this->assertSame(
            'validated',
            $result->releaseStatus()
        );

        $this->assertSame(
            [],
            $history->events
        );

        $this->assertSame(
            'validated',
            $state->current
        );
    }

    public function test_package_failure_marks_release_and_history_failed(): void
    {
        $fixture = $this->fixture('package-fail');

        $state = $this->state('uploaded');
        $history = $this->history();

        $orchestrator = $this->orchestrator(
            103,
            $fixture['root'] . DIRECTORY_SEPARATOR . 'missing.zip',
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(103);

        $this->assertFalse($result->isSuccessful());

        $this->assertSame('failed', $state->current);

        $this->assertSame(
            ['start', 'failed'],
            $history->events
        );
    }

    public function test_backup_failure_never_mutates_application(): void
    {
        $fixture = $this->fixture('backup-fail');

        $content = '<?php return "replacement";';

        $relative = 'app/Missing.php';

        $zip = $this->package(
            $fixture['root'],
            [
                'release_id' => 'BGS-SANDBOX-004',
                'version' => '1.0.0',
                'scope' => 'application',
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => $relative,
                    'sha256' => hash('sha256', $content),
                ]],
                'migrations' => [],
            ],
            [
                $relative => $content,
            ]
        );

        $state = $this->state('uploaded');
        $history = $this->history();

        $orchestrator = $this->orchestrator(
            104,
            $zip,
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(104);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('failed', $state->current);

        $this->assertFileDoesNotExist(
            $fixture['application']
            . DIRECTORY_SEPARATOR
            . str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            )
        );

        $this->assertSame(
            ['start', 'failed'],
            $history->events
        );
    }

    public function test_mutation_failure_never_reaches_installed(): void
    {
        $fixture = $this->fixture('mutation-fail');

        $relative = 'app/Existing.php';

        $target =
            $fixture['application']
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . 'Existing.php';

        $this->writeFile(
            $target,
            'ORIGINAL'
        );

        $content = 'NEW';

        $zip = $this->package(
            $fixture['root'],
            [
                'release_id' => 'BGS-SANDBOX-005',
                'version' => '1.0.0',
                'scope' => 'application',
                'files' => [[
                    'operation' => 'add',
                    'relative_path' => $relative,
                    'sha256' => hash('sha256', $content),
                ]],
                'migrations' => [],
            ],
            [
                $relative => $content,
            ]
        );

        $state = $this->state('uploaded');
        $history = $this->history();

        $orchestrator = $this->orchestrator(
            105,
            $zip,
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(105);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('failed', $state->current);

        $this->assertSame(
            'ORIGINAL',
            file_get_contents($target)
        );

        $this->assertSame(
            ['start', 'failed'],
            $history->events
        );
    }

    public function test_migration_failure_never_marks_installed(): void
    {
        $fixture = $this->fixture('migration-fail');

        $fileRelative = 'app/MigrationAttempt.php';
        $fileContent = '<?php return "must-be-compensated";';
        $replaceRelative = 'app/MigrationReplace.php';
        $replaceOriginal = '<?php return "replace-original";';
        $replaceContent = '<?php return "replace-deployed";';
        $deleteRelative = 'app/MigrationDelete.php';
        $deleteOriginal = '<?php return "delete-original";';

        $this->writeFile(
            $fixture['application'] . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $replaceRelative),
            $replaceOriginal
        );
        $this->writeFile(
            $fixture['application'] . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $deleteRelative),
            $deleteOriginal
        );

        $name =
            '2026_08_24_130000_sandbox.php';

        $relative =
            'database/migrations/' . $name;

        $content =
            '<?php return true;';

        $zip = $this->package(
            $fixture['root'],
            [
                'release_id' => 'BGS-SANDBOX-006',
                'version' => '1.0.0',
                'scope' => 'application',
                'files' => [[
                    'operation' => 'add',
                    'relative_path' => $fileRelative,
                    'sha256' => hash('sha256', $fileContent),
                ], [
                    'operation' => 'replace',
                    'relative_path' => $replaceRelative,
                    'sha256' => hash('sha256', $replaceContent),
                ], [
                    'operation' => 'delete',
                    'relative_path' => $deleteRelative,
                ]],
                'migrations' => [[
                    'migration_name' => $name,
                    'relative_path' => $relative,
                    'sha256' => hash('sha256', $content),
                ]],
            ],
            [
                $fileRelative => $fileContent,
                $replaceRelative => $replaceContent,
                $relative => $content,
            ]
        );

        $state = $this->state('uploaded');
        $history = $this->history();

        $migrationManager =
            new DeploymentMigrationManager(
                $this->migrationRepository(),
                static function (): void {
                    throw new RuntimeException(
                        'Injected migration failure.'
                    );
                }
            );

        $orchestrator = $this->orchestrator(
            106,
            $zip,
            $fixture,
            $state,
            $history,
            $migrationManager
        );

        $result = $orchestrator->install(106);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('failed', $state->current);

        $this->assertSame(
            ['start', 'failed'],
            $history->events
        );

        $this->assertStringContainsString(
            'migration',
            strtolower(
                implode(' | ', $result->errors())
            )
        );

        $this->assertFileDoesNotExist(
            $fixture['application']
                . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $fileRelative),
            'Migration failure must compensate file mutations from this attempt.'
        );
        $this->assertSame(
            $replaceOriginal,
            file_get_contents(
                $fixture['application'] . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $replaceRelative)
            )
        );
        $this->assertSame(
            $deleteOriginal,
            file_get_contents(
                $fixture['application'] . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $deleteRelative)
            )
        );
    }

    public function test_context_exception_is_converted_to_fail_closed_result(): void
    {
        $fixture = $this->fixture('context-exception');

        $state = $this->state('uploaded');
        $history = $this->history();

        $context =
            new class implements DeploymentExecutionContextProviderInterface {
                public function forRelease(
                    int $releaseId
                ): DeploymentExecutionContext {
                    throw new RuntimeException(
                        'Injected context failure.'
                    );
                }
            };

        $orchestrator =
            new DeploymentOrchestrator(
                $context,
                $state,
                $history,
                new DeploymentPackageProcessor(),
                new DeploymentBackupManager(),
                new DeploymentFileMutator(),
                $this->migrationManager()
            );

        $result = $orchestrator->install(107);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('failed', $state->current);

        $this->assertSame(
            ['start', 'failed'],
            $history->events
        );

        $this->assertStringContainsString(
            'context failure',
            strtolower(
                implode(' | ', $result->errors())
            )
        );
    }

    public function test_lost_validation_race_does_not_fail_another_install_attempt(): void
    {
        $fixture = $this->fixture('validation-race');
        $zip = $this->package($fixture['root'], [
            'release_id' => 'BGS-VALIDATION-RACE',
            'version' => '1.0.0',
            'scope' => 'application',
            'files' => [],
            'migrations' => [],
        ], []);

        $state = new class implements DeploymentReleaseStateRepositoryInterface {
            public string $current = 'uploaded';
            public int $failedCalls = 0;

            public function status(int $releaseId): string
            {
                return $this->current;
            }

            public function markValidated(int $releaseId): void
            {
                $this->current = 'validated';

                throw new RuntimeException(
                    'Another install attempt already claimed validation.'
                );
            }

            public function markInstalled(int $releaseId, ?int $installedBy = null): void
            {
                $this->current = 'installed';
            }

            public function markFailed(int $releaseId, ?string $reason = null): void
            {
                $this->failedCalls++;
                $this->current = 'failed';
            }
        };

        $history = $this->history();
        $orchestrator = $this->orchestrator(
            108,
            $zip,
            $fixture,
            $state,
            $history
        );

        $result = $orchestrator->install(108);

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('validated', $state->current);
        $this->assertSame(0, $state->failedCalls);
        $this->assertSame(['start', 'failed'], $history->events);
    }

    private function orchestrator(
        int $releaseId,
        string $zip,
        array $fixture,
        DeploymentReleaseStateRepositoryInterface $state,
        DeploymentHistoryRepositoryInterface $history,
        ?DeploymentMigrationManager $migrationManager = null
    ): DeploymentOrchestrator {
        $context =
            new class(
                $zip,
                $fixture
            ) implements DeploymentExecutionContextProviderInterface {
                private string $zip;
                private array $fixture;

                public function __construct(
                    string $zip,
                    array $fixture
                ) {
                    $this->zip = $zip;
                    $this->fixture = $fixture;
                }

                public function forRelease(
                    int $releaseId
                ): DeploymentExecutionContext {
                    return new DeploymentExecutionContext(
                        $this->zip,
                        $this->fixture['staging'],
                        $this->fixture['application'],
                        $this->fixture['recovery']
                    );
                }
            };

        return new DeploymentOrchestrator(
            $context,
            $state,
            $history,
            new DeploymentPackageProcessor(),
            new DeploymentBackupManager(),
            new DeploymentFileMutator(),
            $migrationManager
                ?? $this->migrationManager()
        );
    }

    private function state(
        string $initial
    ): DeploymentReleaseStateRepositoryInterface {
        return new class(
            $initial
        ) implements DeploymentReleaseStateRepositoryInterface {
            public string $current;

            public function __construct(
                string $initial
            ) {
                $this->current = $initial;
            }

            public function status(
                int $releaseId
            ): string {
                return $this->current;
            }

            public function markValidated(
                int $releaseId
            ): void {
                $this->current = 'validated';
            }

            public function markInstalled(
                int $releaseId,
                ?int $installedBy = null
            ): void {
                $this->current = 'installed';
            }

            public function markFailed(
                int $releaseId,
                ?string $reason = null
            ): void {
                $this->current = 'failed';
            }
        };
    }

    private function history(): DeploymentHistoryRepositoryInterface
    {
        return new class implements DeploymentHistoryRepositoryInterface {
            public array $events = [];

            public function start(
                int $releaseId,
                string $action,
                ?int $performedBy = null
            ): int {
                $this->events[] = 'start';

                return 501;
            }

            public function markSuccess(
                int $historyId
            ): void {
                $this->events[] = 'success';
            }

            public function markFailed(
                int $historyId,
                ?string $reason = null
            ): void {
                $this->events[] = 'failed';
            }
        };
    }

    private function migrationManager(): DeploymentMigrationManager
    {
        return new DeploymentMigrationManager(
            $this->migrationRepository(),
            static function (
                string $migrationName,
                string $migrationPath
            ): void {
                // Controlled no-op test executor.
            }
        );
    }

    private function migrationRepository(): DeploymentMigrationStateRepositoryInterface
    {
        return new class implements DeploymentMigrationStateRepositoryInterface {
            private array $applied = [];

            public function isApplied(
                int $applicationReleaseId,
                string $migrationName
            ): bool {
                return isset(
                    $this->applied[
                        $applicationReleaseId
                        . ':'
                        . $migrationName
                    ]
                );
            }

            public function markApplied(
                int $applicationReleaseId,
                string $migrationName,
                string $relativePath,
                string $sha256
            ): void {
                $this->applied[
                    $applicationReleaseId
                    . ':'
                    . $migrationName
                ] = true;
            }
        };
    }

    private function fixture(
        string $name
    ): array {
        $root =
            sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'bgs-orchestrator-'
            . $name
            . '-'
            . uniqid('', true);

        $application =
            $root
            . DIRECTORY_SEPARATOR
            . 'application';

        $staging =
            $root
            . DIRECTORY_SEPARATOR
            . 'staging';

        $recovery =
            $root
            . DIRECTORY_SEPARATOR
            . 'recovery';

        mkdir(
            $application,
            0777,
            true
        );

        $this->roots[] = $root;

        return [
            'root' => $root,
            'application' => $application,
            'staging' => $staging,
            'recovery' => $recovery,
        ];
    }

    private function package(
        string $root,
        array $manifest,
        array $payloads
    ): string {
        $zipPath =
            $root
            . DIRECTORY_SEPARATOR
            . 'package.zip';

        $zip = new ZipArchive();

        $open =
            $zip->open(
                $zipPath,
                ZipArchive::CREATE
                | ZipArchive::OVERWRITE
            );

        if ($open !== true) {
            throw new RuntimeException(
                'Unable to create sandbox ZIP.'
            );
        }

        $zip->addFromString(
            'manifest.json',
            json_encode(
                $manifest,
                JSON_THROW_ON_ERROR
            )
        );

        $zip->addEmptyDir('payload');

        foreach ($payloads as $relative => $content) {
            $zip->addFromString(
                'payload/'
                . str_replace('\\', '/', $relative),
                $content
            );
        }

        $zip->close();

        return $zipPath;
    }

    private function writeFile(
        string $path,
        string $content
    ): void {
        $directory =
            dirname($path);

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0777,
                true
            );
        }

        file_put_contents(
            $path,
            $content
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
                $this->removeDirectory($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
