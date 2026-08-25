<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class DatabaseDeploymentMigrationStateRepository implements DeploymentMigrationStateRepositoryInterface
{
    private const TABLE = 'deployment_migrations';

    public function isApplied(
        int $applicationReleaseId,
        string $migrationName
    ): bool {
        $applicationReleaseId =
            $this->validatedApplicationReleaseId(
                $applicationReleaseId
            );

        $migrationName =
            $this->validatedMigrationName(
                $migrationName
            );

        return DB::table(self::TABLE)
            ->where(
                'application_release_id',
                $applicationReleaseId
            )
            ->where(
                'migration_name',
                $migrationName
            )
            ->where(
                'status',
                'executed'
            )
            ->exists();
    }

    public function markApplied(
        int $applicationReleaseId,
        string $migrationName,
        string $relativePath,
        string $sha256
    ): void {
        $applicationReleaseId =
            $this->validatedApplicationReleaseId(
                $applicationReleaseId
            );

        $migrationName =
            $this->validatedMigrationName(
                $migrationName
            );

        $relativePath =
            $this->validatedRelativePath(
                $relativePath
            );

        $sha256 =
            $this->validatedSha256(
                $sha256
            );

        $now = now();

        DB::table(self::TABLE)
            ->updateOrInsert(
                [
                    'application_release_id' =>
                        $applicationReleaseId,

                    'migration_name' =>
                        $migrationName,
                ],
                [
                    'relative_path' =>
                        $relativePath,

                    'sha256' =>
                        strtolower($sha256),

                    'status' =>
                        'executed',

                    'executed_at' =>
                        $now,

                    'rolled_back_at' =>
                        null,

                    'updated_at' =>
                        $now,

                    'created_at' =>
                        $now,
                ]
            );
    }

    private function validatedApplicationReleaseId(
        int $applicationReleaseId
    ): int {
        if ($applicationReleaseId <= 0) {
            throw new InvalidArgumentException(
                'Application release ID must be greater than zero.'
            );
        }

        return $applicationReleaseId;
    }

    private function validatedMigrationName(
        string $migrationName
    ): string {
        if (trim($migrationName) === '') {
            throw new InvalidArgumentException(
                'Migration name must not be empty.'
            );
        }

        return $migrationName;
    }

    private function validatedRelativePath(
        string $relativePath
    ): string {
        if (trim($relativePath) === '') {
            throw new InvalidArgumentException(
                'Migration relative path must not be empty.'
            );
        }

        return $relativePath;
    }

    private function validatedSha256(
        string $sha256
    ): string {
        if (
            !preg_match(
                '/^[a-f0-9]{64}$/i',
                $sha256
            )
        ) {
            throw new InvalidArgumentException(
                'Migration SHA-256 must contain exactly 64 hexadecimal characters.'
            );
        }

        return $sha256;
    }
}
