<?php

namespace App\Services\Deployment;

interface DeploymentReleaseStateRepositoryInterface
{
    public function status(int $releaseId): string;

    public function markValidated(int $releaseId): void;

    public function markInstalled(
        int $releaseId,
        ?int $installedBy = null
    ): void;

    public function markFailed(
        int $releaseId,
        ?string $reason = null
    ): void;
}
