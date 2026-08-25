<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseDeploymentReleaseStateRepository
    implements DeploymentReleaseStateRepositoryInterface
{
    private DeploymentStateTransitionPolicy $policy;

    public function __construct(
        DeploymentStateTransitionPolicy $policy
    ) {
        $this->policy = $policy;
    }

    public function status(int $releaseId): string
    {
        $status = DB::table('application_releases')
            ->where('id', $releaseId)
            ->value('status');

        if (!is_string($status) || $status === '') {
            throw new RuntimeException(
                'Deployment release not found.'
            );
        }

        return $status;
    }

    public function markValidated(int $releaseId): void
    {
        $this->transition(
            $releaseId,
            'validated'
        );
    }

    public function markInstalled(
        int $releaseId,
        ?int $installedBy = null
    ): void
    {
        $this->transition(
            $releaseId,
            'installed',
            $installedBy
        );
    }

    public function markFailed(
        int $releaseId,
        ?string $reason = null
    ): void {
        $this->transition(
            $releaseId,
            'failed'
        );
    }

    private function transition(
        int $releaseId,
        string $targetStatus,
        ?int $actorId = null
    ): void {
        DB::transaction(function () use (
            $releaseId,
            $targetStatus,
            $actorId
        ): void {
            $row = DB::table('application_releases')
                ->where('id', $releaseId)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new RuntimeException(
                    'Deployment release not found.'
                );
            }

            $currentStatus =
                (string) $row->status;

            if (
                !$this->policy->canTransition(
                    $currentStatus,
                    $targetStatus
                )
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Illegal deployment state transition: %s -> %s.',
                        $currentStatus,
                        $targetStatus
                    )
                );
            }

            $now = now();

            $updates = [
                'status' => $targetStatus,
                'updated_at' => $now,
            ];

            if ($targetStatus === 'validated') {
                $updates['validated_at'] = $now;
            }

            if ($targetStatus === 'installed') {
                $updates['installed_at'] = $now;
                $updates['installed_by'] = $actorId;
            }

            $updated = DB::table('application_releases')
                ->where('id', $releaseId)
                ->where('status', $currentStatus)
                ->update($updates);

            if ($updated !== 1) {
                throw new RuntimeException(
                    'Deployment release state changed concurrently.'
                );
            }
        });
    }
}
