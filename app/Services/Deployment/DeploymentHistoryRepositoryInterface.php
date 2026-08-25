<?php

namespace App\Services\Deployment;

interface DeploymentHistoryRepositoryInterface
{
    public function start(
        int $releaseId,
        string $action,
        ?int $performedBy = null
    ): int;

    public function markSuccess(int $historyId): void;

    public function markFailed(
        int $historyId,
        ?string $reason = null
    ): void;
}
