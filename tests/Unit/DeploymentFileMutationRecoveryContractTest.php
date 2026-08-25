<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentBackupManager;
use App\Services\Deployment\DeploymentFileMutator;
use App\Services\Deployment\DeploymentFileOperationExecutorInterface;
use App\Services\Deployment\DeploymentPackageManifest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class DeploymentFileMutationRecoveryContractTest extends TestCase
{
    public function test_operation_executor_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(
                DeploymentFileOperationExecutorInterface::class
            ),
            'DeploymentFileOperationExecutorInterface is not implemented.'
        );
    }

    public function test_local_operation_executor_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\LocalDeploymentFileOperationExecutor::class
            ),
            'LocalDeploymentFileOperationExecutor is not implemented.'
        );
    }

    public function test_executor_contract_exposes_apply_method(): void
    {
        $this->requireExecutorContract();

        $this->assertTrue(
            method_exists(
                DeploymentFileOperationExecutorInterface::class,
                'apply'
            ),
            'Operation executor must expose apply().'
        );
    }

    public function test_mutator_accepts_operation_executor_dependency(): void
    {
        $this->requireExecutorContract();

        $reflection =
            new ReflectionClass(
                DeploymentFileMutator::class
            );

        $constructor =
            $reflection->getConstructor();

        $this->assertNotNull(
            $constructor,
            'DeploymentFileMutator must expose constructor injection.'
        );

        $parameters =
            $constructor->getParameters();

        $found = false;

        foreach ($parameters as $parameter) {

            $type =
                $parameter->getType();

            if (
                $type instanceof \ReflectionNamedType
                && !$type->isBuiltin()
                && $type->getName()
                    === DeploymentFileOperationExecutorInterface::class
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'DeploymentFileMutator must accept operation executor dependency.'
        );
    }

    public function test_first_replace_is_restored_when_second_replace_fails(): void
    {
        $this->requireExecutorContract();

        $app =
            $this->root('replace-failure-app');

        $staging =
            $this->staging('replace-failure');

        $this->write(
            $app,
            'app/A.php',
            'A-OLD'
        );

        $this->write(
            $app,
            'app/B.php',
            'B-OLD'
        );

        $this->writePayload(
            $staging,
            'app/A.php',
            'A-NEW'
        );

        $this->writePayload(
            $staging,
            'app/B.php',
            'B-NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/A.php',
                    'sha256' =>
                        hash('sha256', 'A-NEW'),
                ],
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/B.php',
                    'sha256' =>
                        hash('sha256', 'B-NEW'),
                ],
            ]);

        $recovery =
            $this->recovery('replace-failure');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $app,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $mutator =
            new DeploymentFileMutator(
                $this->failOnOperation(2)
            );

        $result =
            $mutator->mutate(
                $app,
                $staging,
                $manifest,
                $backup
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'A-OLD',
            file_get_contents(
                $this->path(
                    $app,
                    'app/A.php'
                )
            )
        );

        $this->assertSame(
            'B-OLD',
            file_get_contents(
                $this->path(
                    $app,
                    'app/B.php'
                )
            )
        );
    }

    public function test_successful_add_is_removed_when_later_operation_fails(): void
    {
        $this->requireExecutorContract();

        $app =
            $this->root('add-failure-app');

        $staging =
            $this->staging('add-failure');

        $this->write(
            $app,
            'app/B.php',
            'B-OLD'
        );

        $this->writePayload(
            $staging,
            'app/New.php',
            'NEW'
        );

        $this->writePayload(
            $staging,
            'app/B.php',
            'B-NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'add',
                    'relative_path' =>
                        'app/New.php',
                    'sha256' =>
                        hash('sha256', 'NEW'),
                ],
                [
                    'operation' => 'replace',
                    'relative_path' =>
                        'app/B.php',
                    'sha256' =>
                        hash('sha256', 'B-NEW'),
                ],
            ]);

        $recovery =
            $this->recovery('add-failure');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $app,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $result =
            (new DeploymentFileMutator(
                $this->failOnOperation(2)
            ))
            ->mutate(
                $app,
                $staging,
                $manifest,
                $backup
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertFileDoesNotExist(
            $this->path(
                $app,
                'app/New.php'
            )
        );

        $this->assertSame(
            'B-OLD',
            file_get_contents(
                $this->path(
                    $app,
                    'app/B.php'
                )
            )
        );
    }

    public function test_successful_delete_is_restored_when_later_operation_fails(): void
    {
        $this->requireExecutorContract();

        $app =
            $this->root('delete-failure-app');

        $staging =
            $this->staging('delete-failure');

        $this->write(
            $app,
            'resources/views/A.blade.php',
            'A-OLD'
        );

        $this->writePayload(
            $staging,
            'app/New.php',
            'NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'delete',
                    'relative_path' =>
                        'resources/views/A.blade.php',
                ],
                [
                    'operation' => 'add',
                    'relative_path' =>
                        'app/New.php',
                    'sha256' =>
                        hash('sha256', 'NEW'),
                ],
            ]);

        $recovery =
            $this->recovery('delete-failure');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $app,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $result =
            (new DeploymentFileMutator(
                $this->failOnOperation(2)
            ))
            ->mutate(
                $app,
                $staging,
                $manifest,
                $backup
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'A-OLD',
            file_get_contents(
                $this->path(
                    $app,
                    'resources/views/A.blade.php'
                )
            )
        );

        $this->assertFileDoesNotExist(
            $this->path(
                $app,
                'app/New.php'
            )
        );
    }

    public function test_failure_on_first_operation_causes_zero_mutation(): void
    {
        $this->requireExecutorContract();

        $app =
            $this->root('first-failure-app');

        $staging =
            $this->staging('first-failure');

        $this->write(
            $app,
            'app/A.php',
            'A-OLD'
        );

        $this->writePayload(
            $staging,
            'app/A.php',
            'A-NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' =>
                        'app/A.php',
                    'sha256' =>
                        hash('sha256', 'A-NEW'),
                ],
            ]);

        $recovery =
            $this->recovery('first-failure');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $app,
                    $recovery,
                    $manifest
                );

        $result =
            (new DeploymentFileMutator(
                $this->failOnOperation(1)
            ))
            ->mutate(
                $app,
                $staging,
                $manifest,
                $backup
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'A-OLD',
            file_get_contents(
                $this->path(
                    $app,
                    'app/A.php'
                )
            )
        );
    }

    public function test_mid_mutation_failure_returns_no_successful_file_set(): void
    {
        $this->requireExecutorContract();

        $app =
            $this->root('result-app');

        $staging =
            $this->staging('result');

        $this->write(
            $app,
            'app/A.php',
            'A-OLD'
        );

        $this->write(
            $app,
            'app/B.php',
            'B-OLD'
        );

        $this->writePayload(
            $staging,
            'app/A.php',
            'A-NEW'
        );

        $this->writePayload(
            $staging,
            'app/B.php',
            'B-NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' =>
                        'app/A.php',
                    'sha256' =>
                        hash('sha256', 'A-NEW'),
                ],
                [
                    'operation' => 'replace',
                    'relative_path' =>
                        'app/B.php',
                    'sha256' =>
                        hash('sha256', 'B-NEW'),
                ],
            ]);

        $recovery =
            $this->recovery('result');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $app,
                    $recovery,
                    $manifest
                );

        $result =
            (new DeploymentFileMutator(
                $this->failOnOperation(2)
            ))
            ->mutate(
                $app,
                $staging,
                $manifest,
                $backup
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            [],
            $result->files()
        );
    }


    private function failOnOperation(
        int $failureAt
    ): DeploymentFileOperationExecutorInterface {

        $realClass =
            \App\Services\Deployment\LocalDeploymentFileOperationExecutor::class;

        if (!class_exists($realClass)) {
            $this->fail(
                'LocalDeploymentFileOperationExecutor is not implemented.'
            );
        }

        $real =
            new $realClass();

        return new class(
            $real,
            $failureAt
        ) implements DeploymentFileOperationExecutorInterface {

            private int $count = 0;

            public function __construct(
                private DeploymentFileOperationExecutorInterface $real,
                private int $failureAt
            ) {
            }

            public function apply(
                array $operation
            ): array {

                $this->count++;

                if (
                    $this->count
                    === $this->failureAt
                ) {
                    throw new RuntimeException(
                        'Injected mid-mutation failure.'
                    );
                }

                return $this->real->apply(
                    $operation
                );
            }
        };
    }


    private function requireExecutorContract(): void
    {
        if (!interface_exists(
            DeploymentFileOperationExecutorInterface::class
        )) {
            $this->fail(
                'DeploymentFileOperationExecutorInterface is not implemented.'
            );
        }
    }


    private function manifest(
        array $files
    ): DeploymentPackageManifest {

        return new DeploymentPackageManifest(
            'BGS-RECOVERY-001',
            '1.0.0',
            'module',
            'test',
            null,
            $files,
            []
        );
    }


    private function root(
        string $suffix
    ): string {

        $root =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-recovery-app-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $root,
            0777,
            true
        );

        return $root;
    }


    private function staging(
        string $suffix
    ): string {

        $root =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-recovery-staging-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $root
            .DIRECTORY_SEPARATOR
            .'payload',
            0777,
            true
        );

        return $root;
    }


    private function recovery(
        string $suffix
    ): string {

        return
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-recovery-set-'
            .$suffix
            .'-'
            .uniqid('', true);
    }


    private function writePayload(
        string $staging,
        string $relativePath,
        string $content
    ): void {

        $this->write(
            $staging
            .DIRECTORY_SEPARATOR
            .'payload',
            $relativePath,
            $content
        );
    }


    private function write(
        string $root,
        string $relativePath,
        string $content
    ): void {

        $path =
            $this->path(
                $root,
                $relativePath
            );

        if (!is_dir(dirname($path))) {
            mkdir(
                dirname($path),
                0777,
                true
            );
        }

        file_put_contents(
            $path,
            $content
        );
    }


    private function path(
        string $root,
        string $relativePath
    ): string {

        return
            $root
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );
    }
}
