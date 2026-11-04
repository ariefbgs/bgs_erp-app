<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Runs before PHPUnit database traits can rebuild a schema.
        if ($app->configurationIsCached() || !$app->environment('testing')) {
            throw new \RuntimeException('SAFETY BLOCK: uncached testing environment required.');
        }

        if (in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, class_uses_recursive($this), true)
            || in_array(\Illuminate\Foundation\Testing\DatabaseMigrations::class, class_uses_recursive($this), true)) {
            throw new \RuntimeException('SAFETY BLOCK: schema-rebuilding tests require a separate disposable database workflow.');
        }

        $connection = $app->make('db')->connection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();
        if ($driver === 'sqlite' && $database === ':memory:') {
            return $app;
        }

        if ($driver !== 'mysql' || $database !== 'erp_app_staging_testing_1026'
            || $connection->getConfig('host') !== '127.0.0.1'
            || (int) $connection->getConfig('port') !== 3306) {
            throw new \RuntimeException('SAFETY BLOCK: exact local ERP testing connection required.');
        }

        if ($connection->selectOne('SELECT DATABASE() AS db')->db !== 'erp_app_staging_testing_1026') {
            throw new \RuntimeException('SAFETY BLOCK: actual database mismatch.');
        }

        $schema = $connection->getSchemaBuilder();
        foreach (['invoice_customers', 'po_customers', 'delivery_orders'] as $table) {
            if (!$schema->hasTable($table)) {
                throw new \RuntimeException('SAFETY BLOCK: ERP baseline missing.');
            }
        }
        foreach (['participants', 'programs', 'membership_application_intakes'] as $table) {
            if ($schema->hasTable($table)) {
                throw new \RuntimeException('SAFETY BLOCK: unexpected NEXUS schema.');
            }
        }

        return $app;
    }
}
