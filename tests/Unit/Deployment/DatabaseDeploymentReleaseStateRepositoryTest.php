<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DatabaseDeploymentReleaseStateRepository;
use App\Services\Deployment\DeploymentStateTransitionPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class DatabaseDeploymentReleaseStateRepositoryTest
    extends TestCase
{
    private DatabaseDeploymentReleaseStateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('application_releases')->delete();

        $this->repository =
            new DatabaseDeploymentReleaseStateRepository(
                new DeploymentStateTransitionPolicy()
            );
    }

    protected function tearDown(): void
    {
        DB::table('application_releases')->delete();

        parent::tearDown();
    }

    public function test_uploaded_can_be_marked_validated(): void
    {
        $id = $this->release('uploaded');

        $this->repository->markValidated($id);

        $this->assertSame(
            'validated',
            $this->repository->status($id)
        );

        $release = DB::table('application_releases')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($release);
        $this->assertNotNull($release->validated_at);
        $this->assertNull($release->installed_at);
    }

    public function test_validated_can_be_marked_installed(): void
    {
        $id = $this->release('validated');

        $this->repository->markInstalled($id);

        $this->assertSame(
            'installed',
            $this->repository->status($id)
        );

        $release = DB::table('application_releases')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($release);
        $this->assertNotNull($release->installed_at);
    }

    public function test_uploaded_can_be_marked_failed(): void
    {
        $id = $this->release('uploaded');

        $this->repository->markFailed(
            $id,
            'validation failed'
        );

        $this->assertSame(
            'failed',
            $this->repository->status($id)
        );
    }

    public function test_installed_transition_persists_actor_identity(): void
    {
        $id = $this->release('validated');

        $this->repository->markInstalled($id, 42);

        $this->assertSame(
            42,
            (int) DB::table('application_releases')
                ->where('id', $id)
                ->value('installed_by')
        );
    }

    public function test_validated_can_be_marked_failed(): void
    {
        $id = $this->release('validated');

        $this->repository->markFailed(
            $id,
            'installation failed'
        );

        $this->assertSame(
            'failed',
            $this->repository->status($id)
        );
    }

    public function test_uploaded_cannot_jump_directly_to_installed(): void
    {
        $id = $this->release('uploaded');

        try {
            $this->repository->markInstalled($id);

            $this->fail(
                'Illegal transition was not rejected.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString(
                'Illegal deployment state transition',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'uploaded',
            $this->repository->status($id)
        );
    }

    public function test_failed_cannot_become_installed(): void
    {
        $id = $this->release('failed');

        try {
            $this->repository->markInstalled($id);

            $this->fail(
                'Terminal-state re-entry was not rejected.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString(
                'Illegal deployment state transition',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'failed',
            $this->repository->status($id)
        );
    }

    public function test_same_state_transition_is_rejected(): void
    {
        $id = $this->release('validated');

        try {
            $this->repository->markValidated($id);

            $this->fail(
                'Same-state transition was not rejected.'
            );
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString(
                'Illegal deployment state transition',
                $exception->getMessage()
            );
        }

        $this->assertSame(
            'validated',
            $this->repository->status($id)
        );
    }

    public function test_nonexistent_release_is_rejected(): void
    {
        $this->expectException(
            \RuntimeException::class
        );

        $this->expectExceptionMessage(
            'Deployment release not found.'
        );

        $this->repository->markValidated(999999);
    }

    private function release(string $status): int
    {
        $identity = uniqid();

        return (int) DB::table(
            'application_releases'
        )->insertGetId([
            'release_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'release_id' => 'TEST-' . $identity,
            'name' => 'Test Release ' . $identity,
            'scope' => 'application',
            'version' => 'test-' . $identity,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
