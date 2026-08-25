<?php

namespace App\Services\Deployment;

use Throwable;

final class DeploymentMigrationManager
{
    private DeploymentMigrationStateRepositoryInterface $repository;

    /** @var callable */
    private $executor;

    public function __construct(
        DeploymentMigrationStateRepositoryInterface $repository,
        callable $executor
    ) {
        $this->repository = $repository;
        $this->executor = $executor;
    }

    public function execute(
        int $applicationReleaseId,
        string $stagingDirectory,
        DeploymentPackageManifest $manifest
    ): DeploymentMigrationResult {
        $applied = [];
        $skipped = [];

        foreach ($manifest->migrations() as $migration) {
            $validationError =
                $this->validateMigrationDefinition($migration);

            if ($validationError !== null) {
                return DeploymentMigrationResult::failure(
                    [$validationError],
                    $applied,
                    $skipped
                );
            }

            $name = $migration['migration_name'];
            $relativePath = $migration['relative_path'];
            $expectedSha = strtolower($migration['sha256']);

            $payloadPath =
                rtrim(
                    $stagingDirectory,
                    DIRECTORY_SEPARATOR
                )
                .DIRECTORY_SEPARATOR
                .'payload'
                .DIRECTORY_SEPARATOR
                .str_replace(
                    ['/', '\\'],
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );

            if (!is_file($payloadPath)) {
                return DeploymentMigrationResult::failure(
                    [
                        'Migration payload is missing: '.$name,
                    ],
                    $applied,
                    $skipped,
                    $name
                );
            }

            $actualSha =
                hash_file(
                    'sha256',
                    $payloadPath
                );

            if (
                !is_string($actualSha)
                || !hash_equals(
                    $expectedSha,
                    strtolower($actualSha)
                )
            ) {
                return DeploymentMigrationResult::failure(
                    [
                        'Migration checksum mismatch: '.$name,
                    ],
                    $applied,
                    $skipped,
                    $name
                );
            }

            if (
                $this->repository->isApplied(
                    $applicationReleaseId,
                    $name
                )
            ) {
                $skipped[] = $name;
                continue;
            }

            try {
                ($this->executor)(
                    $name,
                    $payloadPath
                );

                $this->repository->markApplied(
                    $applicationReleaseId,
                    $name,
                    $relativePath,
                    $expectedSha
                );

                $applied[] = $name;
            } catch (Throwable $exception) {
                return DeploymentMigrationResult::failure(
                    [
                        'Migration execution failed: '
                        .$name
                        .' - '
                        .$exception->getMessage(),
                    ],
                    $applied,
                    $skipped,
                    $name
                );
            }
        }

        return DeploymentMigrationResult::success(
            $applied,
            $skipped
        );
    }

    private function validateMigrationDefinition(
        array $migration
    ): ?string {
        $name =
            $migration['migration_name'] ?? null;

        $relativePath =
            $migration['relative_path'] ?? null;

        $sha =
            $migration['sha256'] ?? null;

        if (
            !is_string($name)
            || $name === ''
        ) {
            return 'Migration name is invalid.';
        }

        if (
            !is_string($relativePath)
            || $relativePath === ''
        ) {
            return 'Migration path is invalid: '.$name;
        }

        $normalized =
            str_replace(
                '\\',
                '/',
                $relativePath
            );

        if (
            !str_starts_with(
                $normalized,
                'database/migrations/'
            )
            || pathinfo(
                $normalized,
                PATHINFO_EXTENSION
            ) !== 'php'
        ) {
            return 'Migration path is unsafe: '.$name;
        }

        if (
            !is_string($sha)
            || !preg_match(
                '/^[a-f0-9]{64}$/i',
                $sha
            )
        ) {
            return 'Migration checksum is invalid: '.$name;
        }

        return null;
    }
}