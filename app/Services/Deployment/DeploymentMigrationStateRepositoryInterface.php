<?php

namespace App\Services\Deployment;

interface DeploymentMigrationStateRepositoryInterface
{
    public function isApplied(
        int $applicationReleaseId,
        string $migrationName
    ): bool;

    public function markApplied(
        int $applicationReleaseId,
        string $migrationName,
        string $relativePath,
        string $sha256
    ): void;
}
