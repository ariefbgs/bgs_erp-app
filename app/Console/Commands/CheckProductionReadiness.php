<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Throwable;

class CheckProductionReadiness extends Command
{
    protected $signature = 'bgs:production-check {expected_sha : Approved 40-character source commit}';
    protected $description = 'Read-only local production preflight; does not approve go-live or execute deployment';

    public function handle(): int
    {
        $sha = (string) $this->argument('expected_sha');
        $release = json_decode((string) @file_get_contents(base_path('release.json')), true) ?? [];
        $key = (string) config('app.key');
        $url = parse_url((string) config('app.url'));
        $checks = [
            'php_84_or_newer' => PHP_VERSION_ID >= 80400,
            'extensions' => array_reduce(['pdo_mysql', 'mbstring', 'xml', 'ctype', 'fileinfo', 'gd', 'zip', 'intl', 'bcmath'],
                fn ($ok, $extension) => $ok && extension_loaded($extension), true),
            'environment_production' => app()->environment('production'),
            'debug_disabled' => config('app.debug') === false,
            'https_url' => ($url['scheme'] ?? '') === 'https' && !empty($url['host'])
                && !in_array(strtolower($url['host'] ?? ''), ['localhost', '127.0.0.1'], true)
                && !str_ends_with(strtolower($url['host'] ?? ''), '.invalid'),
            'application_key' => str_starts_with($key, 'base64:') && strlen((string) base64_decode(substr($key, 7), true)) === 32,
            'secure_cookie' => config('session.secure') === true,
            'registration_disabled' => !collect(Route::getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->uri() === 'register'),
            'fresh_identity_policy' => config('bgs_access.restricted_full_user_ids') === [],
            'configuration_cached' => app()->configurationIsCached(),
            'release_identity' => preg_match('/^[a-f0-9]{40}$/', $sha) === 1 && ($release['source_sha'] ?? null) === $sha,
            'assets_built' => is_file(public_path('build/manifest.json')) && !is_file(public_path('hot')),
            'public_storage_link' => is_link(public_path('storage')) && realpath(public_path('storage')) === realpath(storage_path('app/public')),
            'runtime_writable' => is_writable(storage_path()) && is_writable(app()->bootstrapPath('cache')),
        ];
        try {
            $connection = DB::connection();
            $identity = $connection->selectOne('SELECT DATABASE() AS name, VERSION() AS version');
            $checks['mysql_84'] = $connection->getDriverName() === 'mysql'
                && !str_contains($identity->version, 'MariaDB') && version_compare($identity->version, '8.4', '>=');
            $checks['database_identity'] = $identity->name === $connection->getDatabaseName();
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $checks['migrations_current'] = array_diff(array_keys($files), $ran) === [];
            $checks['administrator_present'] = $connection->table('users')->where('role', 'admin')->exists();
            $checks['permission_seed_present'] = $connection->table('role_permissions as rp')
                ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
                ->where('rp.role', 'admin')->where('p.name', 'deployment_view')->exists();
        } catch (Throwable) {
            // Connection errors can contain private hostnames/usernames; omit them.
            $checks['database_checks_complete'] = false;
        }
        $pass = !in_array(false, $checks, true);
        $this->line(json_encode(['status' => $pass ? 'LOCAL_CHECKS_PASS' : 'BLOCKED', 'checks' => $checks,
            'source_sha' => $release['source_sha'] ?? null, 'database_writes' => 0,
            'external_gates' => ['TLS/document root/FPM', 'backup and restore evidence', 'scheduler', 'approved data opening and UAT', 'release tag and deployment approval'],
            'go_live_approved' => false], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        return $pass ? 0 : 1;
    }
}
