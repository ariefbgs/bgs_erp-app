<?php

namespace Tests\Unit;

use Tests\TestCase;

class InvPartial30F5B2DatabaseRuntimeProbeTest extends TestCase
{
    public function test_phpunit_runtime_database_is_isolated(): void
    {
        $environment = app()->environment();
        $connection = config('database.default');
        $database = config(
            'database.connections.' . $connection . '.database'
        );

        fwrite(
            STDERR,
            PHP_EOL
            . 'RUNTIME_APP_ENV=' . $environment . PHP_EOL
            . 'RUNTIME_DB_CONNECTION=' . $connection . PHP_EOL
            . 'RUNTIME_DB_DATABASE=' . $database . PHP_EOL
        );

        $this->assertSame('testing', $environment);
        $this->assertSame('mysql', $connection);
        $this->assertSame('erp_app_staging_testing_1026', $database);
        $this->assertNotSame('erp_app_staging', $database);
    }
}