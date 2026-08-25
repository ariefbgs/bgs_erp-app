<?php

namespace App\Services\Deployment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class DatabaseDeploymentHistoryRepository
    implements DeploymentHistoryRepositoryInterface
{
    public function start(
        int $releaseId,
        string $action,
        ?int $performedBy = null
    ): int {
        return (int) DB::table('deployment_history')
            ->insertGetId([
                'deployment_uuid' => (string) Str::uuid(),
                'application_release_id' => $releaseId,
                'action' => $action,
                'status' => 'started',
                'performed_by' => $performedBy,
                'started_at' => now(),
                'completed_at' => null,
                'summary' => null,
                'error_message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markSuccess(
        int $historyId
    ): void {
        $this->complete(
            $historyId,
            'success',
            null
        );
    }

    public function markFailed(
        int $historyId,
        ?string $reason = null
    ): void {
        $this->complete(
            $historyId,
            'failed',
            $reason
        );
    }

    private function complete(
        int $historyId,
        string $status,
        ?string $reason
    ): void {
        DB::transaction(function () use (
            $historyId,
            $status,
            $reason
        ): void {
            $row = DB::table('deployment_history')
                ->where('id', $historyId)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new RuntimeException(
                    'Deployment history not found.'
                );
            }

            if ((string) $row->status !== 'started') {
                throw new RuntimeException(
                    'Deployment history is already terminal.'
                );
            }

            $updated = DB::table('deployment_history')
                ->where('id', $historyId)
                ->where('status', 'started')
                ->update([
                    'status' => $status,
                    'completed_at' => now(),
                    'error_message' =>
                        $status === 'failed'
                            ? $reason
                            : null,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new RuntimeException(
                    'Deployment history changed concurrently.'
                );
            }
        });
    }
}
