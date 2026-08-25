<?php

namespace App\Services\Deployment;

use Throwable;

final class DeploymentOrchestrator
{
    private DeploymentExecutionContextProviderInterface $executionContextProvider;

    private DeploymentReleaseStateRepositoryInterface $releaseStateRepository;

    private DeploymentHistoryRepositoryInterface $historyRepository;

    private DeploymentPackageProcessor $packageProcessor;

    private DeploymentBackupManager $backupManager;

    private DeploymentFileMutator $fileMutator;

    private DeploymentMigrationManager $migrationManager;

    public function __construct(
        DeploymentExecutionContextProviderInterface $executionContextProvider,
        DeploymentReleaseStateRepositoryInterface $releaseStateRepository,
        DeploymentHistoryRepositoryInterface $historyRepository,
        DeploymentPackageProcessor $packageProcessor,
        DeploymentBackupManager $backupManager,
        DeploymentFileMutator $fileMutator,
        DeploymentMigrationManager $migrationManager
    ) {
        $this->executionContextProvider =
            $executionContextProvider;

        $this->releaseStateRepository =
            $releaseStateRepository;

        $this->historyRepository =
            $historyRepository;

        $this->packageProcessor =
            $packageProcessor;

        $this->backupManager =
            $backupManager;

        $this->fileMutator =
            $fileMutator;

        $this->migrationManager =
            $migrationManager;
    }

    public function install(
        int $releaseId,
        ?int $performedBy = null
    ): DeploymentOrchestrationResult {

        $historyId = null;
        $context = null;
        $validatedByAttempt = false;

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. RELEASE PRECONDITION
            |--------------------------------------------------------------------------
            */

            $currentStatus =
                $this->releaseStateRepository
                    ->status($releaseId);

            if ($currentStatus !== 'uploaded') {
                return DeploymentOrchestrationResult::failure(
                    [
                        'Deployment release must be in uploaded status before installation.',
                    ],
                    $currentStatus
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 2. DURABLE INSTALL ATTEMPT
            |--------------------------------------------------------------------------
            */

            $historyId =
                $this->historyRepository
                    ->start(
                        $releaseId,
                        'install',
                        $performedBy
                    );


            /*
            |--------------------------------------------------------------------------
            | 3. EXECUTION CONTEXT
            |--------------------------------------------------------------------------
            */

            $context =
                $this->executionContextProvider
                    ->forRelease($releaseId);

            $expectedPackageSha256 =
                $context->expectedPackageSha256();

            if ($expectedPackageSha256 !== null) {
                $actualPackageSha256 = hash_file(
                    'sha256',
                    $context->packagePath()
                );

                if (
                    !preg_match(
                        '/^[a-f0-9]{64}$/',
                        $expectedPackageSha256
                    )
                    || !is_string($actualPackageSha256)
                    || !hash_equals(
                        $expectedPackageSha256,
                        strtolower($actualPackageSha256)
                    )
                ) {
                    return $this->fail(
                        $releaseId,
                        $historyId,
                        ['Registered deployment package checksum mismatch.']
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 4. SECURE PACKAGE PROCESSING
            |--------------------------------------------------------------------------
            */

            $package =
                $this->packageProcessor
                    ->process(
                        $context->packagePath(),
                        $context->stagingPath()
                    );

            if (!$package->isValid()) {
                return $this->fail(
                    $releaseId,
                    $historyId,
                    $package->errors()
                );
            }

            $manifest =
                $package->manifest();

            if ($manifest === null) {
                return $this->fail(
                    $releaseId,
                    $historyId,
                    [
                        'Validated deployment manifest is missing.',
                    ]
                );
            }

            $expectedManifestIdentity =
                $context->expectedManifestIdentity();

            if (
                $expectedManifestIdentity !== null
                && $expectedManifestIdentity !== [
                    'release_id' => $manifest->releaseId(),
                    'version' => $manifest->version(),
                    'scope' => $manifest->scope(),
                    'module' => $manifest->module(),
                    'feature' => $manifest->feature(),
                ]
            ) {
                return $this->fail(
                    $releaseId,
                    $historyId,
                    ['Registered deployment metadata does not match its package manifest.']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 5. PACKAGE VALIDATED
            |--------------------------------------------------------------------------
            */

            $this->releaseStateRepository
                ->markValidated($releaseId);

            $validatedByAttempt = true;


            /*
            |--------------------------------------------------------------------------
            | 6. RECOVERY BACKUP
            |--------------------------------------------------------------------------
            */

            $backup =
                $this->backupManager
                    ->backup(
                        $context->applicationRoot(),
                        $context->recoveryPath(),
                        $manifest
                    );

            if (!$backup->isValid()) {
                return $this->fail(
                    $releaseId,
                    $historyId,
                    $backup->errors(),
                    true
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 7. APPLICATION FILE MUTATION
            |--------------------------------------------------------------------------
            */

            $mutation =
                $this->fileMutator
                    ->mutate(
                        $context->applicationRoot(),
                        $context->stagingPath(),
                        $manifest,
                        $backup
                    );

            if (!$mutation->isValid()) {
                return $this->fail(
                    $releaseId,
                    $historyId,
                    $mutation->errors(),
                    true
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 8. DATABASE MIGRATIONS
            |--------------------------------------------------------------------------
            */

            $migration =
                $this->migrationManager
                    ->execute(
                        $releaseId,
                        $context->stagingPath(),
                        $manifest
                    );

            if (!$migration->isValid()) {
                $compensation = $this->fileMutator
                    ->compensateCompletedMutation(
                        $context->applicationRoot(),
                        $backup,
                        $mutation
                    );

                $errors = $migration->errors();

                if (!$compensation->isValid()) {
                    $errors = array_merge(
                        $errors,
                        ['File compensation after migration failure was incomplete.'],
                        $compensation->errors()
                    );
                }

                return $this->fail(
                    $releaseId,
                    $historyId,
                    $errors,
                    true
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 9. TERMINAL RELEASE COMMIT
            |--------------------------------------------------------------------------
            |
            | installed is never persisted before package, backup, mutation,
            | and migration have all completed successfully.
            |--------------------------------------------------------------------------
            */

            $this->releaseStateRepository
                ->markInstalled($releaseId, $performedBy);


            /*
            |--------------------------------------------------------------------------
            | 10. TERMINAL HISTORY SUCCESS
            |--------------------------------------------------------------------------
            */

            $this->historyRepository
                ->markSuccess($historyId);


            return DeploymentOrchestrationResult::success(
                'installed'
            );

        } catch (Throwable $exception) {

            return $this->fail(
                $releaseId,
                $historyId,
                [
                    'Deployment orchestration failed: '
                    .$exception->getMessage(),
                ],
                $validatedByAttempt
            );
        } finally {
            if ($context instanceof DeploymentExecutionContext) {
                $this->removeStagingDirectory(
                    $context->stagingPath()
                );
            }
        }
    }

    private function removeStagingDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($target) && !is_link($target)) {
                $this->removeStagingDirectory($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }

    /**
     * @param array<int, string> $errors
     */
    private function fail(
        int $releaseId,
        ?int $historyId,
        array $errors,
        bool $validatedByAttempt = false
    ): DeploymentOrchestrationResult {

        $errors =
            array_values(
                array_map(
                    static fn ($error): string =>
                        (string) $error,
                    $errors
                )
            );

        $reason =
            implode(
                ' | ',
                $errors
            );

        /*
        |--------------------------------------------------------------------------
        | RELEASE FAILURE — FAIL CLOSED
        |--------------------------------------------------------------------------
        */

        try {

            $status =
                $this->releaseStateRepository
                    ->status($releaseId);

            if (
                $status === 'uploaded'
                || (
                    $status === 'validated'
                    && $validatedByAttempt
                )
            ) {
                $this->releaseStateRepository
                    ->markFailed(
                        $releaseId,
                        $reason
                    );
            }

        } catch (Throwable $exception) {

            $errors[] =
                'Unable to persist release failure state: '
                .$exception->getMessage();
        }


        /*
        |--------------------------------------------------------------------------
        | HISTORY FAILURE
        |--------------------------------------------------------------------------
        */

        if ($historyId !== null) {

            try {

                $this->historyRepository
                    ->markFailed(
                        $historyId,
                        $reason
                    );

            } catch (Throwable $exception) {

                $errors[] =
                    'Unable to persist deployment history failure: '
                    .$exception->getMessage();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | FINAL OBSERVED STATUS
        |--------------------------------------------------------------------------
        */

        $releaseStatus =
            'failed';

        try {

            $releaseStatus =
                $this->releaseStateRepository
                    ->status($releaseId);

        } catch (Throwable $exception) {
            // Keep safe fallback status.
        }


        return DeploymentOrchestrationResult::failure(
            $errors,
            $releaseStatus
        );
    }
}
