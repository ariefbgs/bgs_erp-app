<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DatabaseDeploymentHistoryRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class DatabaseDeploymentHistoryRepositoryTest
    extends TestCase
{
    private DatabaseDeploymentHistoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('deployment_history')->delete();

        $this->repository =
            new DatabaseDeploymentHistoryRepository();
    }

    protected function tearDown(): void
    {
        DB::table('deployment_history')->delete();

        parent::tearDown();
    }

    public function test_start_creates_started_history(): void
    {
        $id = $this->repository->start(
            10,
            'install'
        );

        $row = DB::table('deployment_history')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($row);

        $this->assertNotNull(
            $row->deployment_uuid
        );

        $this->assertTrue(
            Str::isUuid($row->deployment_uuid)
        );

        $this->assertSame('install', $row->action);
        $this->assertSame('started', $row->status);
        $this->assertNotNull($row->started_at);
        $this->assertNull($row->completed_at);
    }

    public function test_start_generates_unique_deployment_uuid(): void
    {
        $firstId = $this->repository->start(
            10,
            'install'
        );

        $secondId = $this->repository->start(
            10,
            'install'
        );

        $first = DB::table('deployment_history')
            ->where('id', $firstId)
            ->first();

        $second = DB::table('deployment_history')
            ->where('id', $secondId)
            ->first();

        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $this->assertTrue(
            Str::isUuid($first->deployment_uuid)
        );

        $this->assertTrue(
            Str::isUuid($second->deployment_uuid)
        );

        $this->assertNotSame(
            $first->deployment_uuid,
            $second->deployment_uuid
        );
    }

    public function test_start_persists_authenticated_actor_identity(): void
    {
        $id = $this->repository->start(10, 'install', 42);

        $this->assertSame(
            42,
            (int) DB::table('deployment_history')
                ->where('id', $id)
                ->value('performed_by')
        );
    }
    public function test_started_history_can_be_marked_success(): void
    {
        $id = $this->repository->start(
            10,
            'install'
        );

        $this->repository->markSuccess($id);

        $row = DB::table('deployment_history')
            ->where('id', $id)
            ->first();

        $this->assertSame('success', $row->status);
        $this->assertNotNull($row->completed_at);
        $this->assertNull($row->error_message);
    }

    public function test_started_history_can_be_marked_failed(): void
    {
        $id = $this->repository->start(
            10,
            'install'
        );

        $this->repository->markFailed(
            $id,
            'migration failed'
        );

        $row = DB::table('deployment_history')
            ->where('id', $id)
            ->first();

        $this->assertSame('failed', $row->status);
        $this->assertNotNull($row->completed_at);
        $this->assertSame(
            'migration failed',
            $row->error_message
        );
    }

    public function test_success_history_cannot_be_changed_to_failed(): void
    {
        $id = $this->repository->start(
            10,
            'install'
        );

        $this->repository->markSuccess($id);

        try {
            $this->repository->markFailed(
                $id,
                'late failure'
            );

            $this->fail(
                'Terminal history mutation was not rejected.'
            );
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'already terminal',
                $exception->getMessage()
            );
        }

        $row = DB::table('deployment_history')
            ->where('id', $id)
            ->first();

        $this->assertSame('success', $row->status);
    }

    public function test_failed_history_cannot_be_changed_to_success(): void
    {
        $id = $this->repository->start(
            10,
            'install'
        );

        $this->repository->markFailed(
            $id,
            'failed'
        );

        try {
            $this->repository->markSuccess($id);

            $this->fail(
                'Terminal history mutation was not rejected.'
            );
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'already terminal',
                $exception->getMessage()
            );
        }

        $row = DB::table('deployment_history')
            ->where('id', $id)
            ->first();

        $this->assertSame('failed', $row->status);
    }

    public function test_nonexistent_history_cannot_be_marked_success(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Deployment history not found.'
        );

        $this->repository->markSuccess(999999);
    }

    public function test_nonexistent_history_cannot_be_marked_failed(): void
    {
        $this->expectException(
            RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Deployment history not found.'
        );

        $this->repository->markFailed(
            999999,
            'failure'
        );
    }
}
