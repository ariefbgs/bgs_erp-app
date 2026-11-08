<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyMasterImportSafetyTest extends TestCase
{
    public function test_missing_snapshot_refuses_without_database_access(): void
    {
        $this->artisan('legacy:load-masters', ['snapshot' => '/nonexistent/private-master.json'])
            ->assertExitCode(1);
    }

    public function test_bad_source_identity_is_rejected_before_database_access(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bgs_etl_');
        try {
            file_put_contents($file, json_encode(['database' => 'wrong_db', 'tables' => []]));
            $this->artisan('legacy:load-masters', ['snapshot' => $file])->assertExitCode(1);
        } finally {
            unlink($file);
        }
    }

    public function test_production_apply_is_blocked_before_reading_snapshot(): void
    {
        $environment = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->artisan('legacy:load-masters', [
                'snapshot' => '/nonexistent/private-master.json', '--apply' => true,
            ])->assertExitCode(1);
        } finally {
            $this->app['env'] = $environment;
        }
    }
}
