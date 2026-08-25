<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\DatabaseDeploymentMigrationStateRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

final class DatabaseDeploymentMigrationProductionContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('deployment_migrations')->delete();
}

    protected function tearDown(): void
    {
        DB::table('deployment_migrations')->delete();

        parent::tearDown();
    }

    public function test_same_migration_name_is_scoped_by_release(): void
    {
        $repository =
            new DatabaseDeploymentMigrationStateRepository();

        $repository->markApplied(
            101,
            '2026_08_24_100000_create_alpha.php',
            'database/migrations/2026_08_24_100000_create_alpha.php',
            str_repeat('a', 64)
        );

        self::assertTrue(
            $repository->isApplied(
                101,
                '2026_08_24_100000_create_alpha.php'
            )
        );

        self::assertFalse(
            $repository->isApplied(
                202,
                '2026_08_24_100000_create_alpha.php'
            )
        );
    }

    public function test_mark_applied_persists_production_metadata(): void
    {
        $repository =
            new DatabaseDeploymentMigrationStateRepository();

        $sha = str_repeat('b', 64);

        $repository->markApplied(
            101,
            '2026_08_24_100001_create_beta.php',
            'database/migrations/2026_08_24_100001_create_beta.php',
            $sha
        );

        $row = DB::table('deployment_migrations')
            ->where('application_release_id', 101)
            ->where(
                'migration_name',
                '2026_08_24_100001_create_beta.php'
            )
            ->first();

        self::assertNotNull($row);

        self::assertSame(
            'database/migrations/2026_08_24_100001_create_beta.php',
            $row->relative_path
        );

        self::assertSame(
            $sha,
            $row->sha256
        );

        self::assertSame(
            'executed',
            $row->status
        );

        self::assertNotNull(
            $row->executed_at
        );

        self::assertNull(
            $row->rolled_back_at
        );
    }

    public function test_mark_applied_is_idempotent_within_release(): void
    {
        $repository =
            new DatabaseDeploymentMigrationStateRepository();

        $name =
            '2026_08_24_100002_create_gamma.php';

        $path =
            'database/migrations/'.$name;

        $sha = str_repeat('c', 64);

        $repository->markApplied(
            101,
            $name,
            $path,
            $sha
        );

        $repository->markApplied(
            101,
            $name,
            $path,
            $sha
        );

        self::assertSame(
            1,
            DB::table('deployment_migrations')
                ->where('application_release_id', 101)
                ->where('migration_name', $name)
                ->count()
        );
    }

    public function test_same_migration_can_exist_in_different_releases(): void
    {
        $repository =
            new DatabaseDeploymentMigrationStateRepository();

        $name =
            '2026_08_24_100003_create_delta.php';

        $path =
            'database/migrations/'.$name;

        $sha = str_repeat('d', 64);

        $repository->markApplied(
            101,
            $name,
            $path,
            $sha
        );

        $repository->markApplied(
            202,
            $name,
            $path,
            $sha
        );

        self::assertSame(
            2,
            DB::table('deployment_migrations')
                ->where('migration_name', $name)
                ->count()
        );
    }

    public function test_empty_migration_name_is_rejected(): void
    {
        $repository =
            new DatabaseDeploymentMigrationStateRepository();

        $this->expectException(
            InvalidArgumentException::class
        );

        $repository->markApplied(
            101,
            '   ',
            'database/migrations/example.php',
            str_repeat('e', 64)
        );
    }
}
