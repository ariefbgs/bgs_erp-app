<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Fail closed: only :memory: SQLite or the dedicated G5.18 MySQL test account may commit ETL. */
final class LegacyEtlTestGuard
{
    public static function assertApplyAllowed(): void
    {
        if (!app()->environment('testing')) {
            throw new RuntimeException('ETL_APPLY_BLOCKED: APP_ENV must be testing');
        }

        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite'
            && $connection->getDatabaseName() === ':memory:') {
            return;
        }
        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException('ETL_APPLY_BLOCKED: unsupported test driver');
        }

        // A hard-coded allowlist is intentional; never accept arbitrary DB names via env.
        $cfg = config('database.connections.'.config('database.default'), []);
        if (getenv('BGS_G518_TEST_APPLY') !== 'YES_G518_ONLY'
            || ($cfg['host'] ?? '') !== '127.0.0.1'
            || (int) ($cfg['port'] ?? 0) !== 13307
            || ($cfg['database'] ?? '') !== 'bgs_erp_etl_test_g518'
            || ($cfg['username'] ?? '') !== 'bgs_g518'
            || !empty($cfg['unix_socket'])) {
            throw new RuntimeException('ETL_APPLY_BLOCKED: G518 connection configuration mismatch');
        }

        $actual = $connection->selectOne('SELECT DATABASE() AS db, CURRENT_USER() AS account');
        if (($actual->db ?? '') !== 'bgs_erp_etl_test_g518'
            || explode('@', (string) ($actual->account ?? ''), 2)[0] !== 'bgs_g518') {
            throw new RuntimeException('ETL_APPLY_BLOCKED: runtime MySQL identity mismatch');
        }
    }
}
