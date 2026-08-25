<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentPackageValidator;
use PHPUnit\Framework\TestCase;

class DeploymentPackageSecurityContractTest extends TestCase
{
    private DeploymentPackageValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new DeploymentPackageValidator();
    }

    private function baseManifest(array $override = []): array
    {
        return array_replace_recursive([
            'release_id' => 'BGS-SEC-001',
            'version' => '1.0.1',
            'scope' => 'module',
            'module' => 'po_supplier',
            'files' => [],
            'migrations' => [],
        ], $override);
    }

    private function assertRejected(
        array $manifest,
        string $expectedFragment
    ): void {
        $result = $this->validator->validate($manifest);

        $this->assertFalse(
            $result->isValid(),
            'Dangerous deployment manifest was unexpectedly accepted.'
        );

        $errors = implode(' | ', $result->errors());

        $this->assertStringContainsString(
            $expectedFragment,
            $errors
        );
    }

    public function test_rejects_parent_directory_traversal(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => '../.env',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'path'
        );
    }

    public function test_rejects_nested_parent_directory_traversal(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => 'app/../.env',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'path'
        );
    }

    public function test_rejects_linux_absolute_path(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => '/etc/passwd',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'path'
        );
    }

    public function test_rejects_windows_absolute_path(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => 'C:\\Windows\\system.ini',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'path'
        );
    }

    public function test_rejects_blocked_env_target(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => '.env',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'blocked'
        );
    }

    public function test_rejects_blocked_runtime_directories(): void
    {
        foreach ([
            'storage/logs/laravel.log',
            'vendor/autoload.php',
            'node_modules/test.js',
            '.git/config',
            'audit-output/test.txt',
        ] as $path) {
            $result = $this->validator->validate(
                $this->baseManifest([
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => $path,
                        'sha256' => str_repeat('a', 64),
                    ]],
                ])
            );

            $this->assertFalse(
                $result->isValid(),
                "Blocked path accepted: {$path}"
            );
        }
    }

    public function test_rejects_unknown_file_operation(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'execute',
                    'relative_path' => 'app/Test.php',
                    'sha256' => str_repeat('a', 64),
                ]],
            ]),
            'operation'
        );
    }

    public function test_rejects_duplicate_file_target(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [
                    [
                        'operation' => 'replace',
                        'relative_path' => 'app/Test.php',
                        'sha256' => str_repeat('a', 64),
                    ],
                    [
                        'operation' => 'delete',
                        'relative_path' => 'app/Test.php',
                    ],
                ],
            ]),
            'duplicate'
        );
    }

    public function test_rejects_missing_sha256_for_add(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'add',
                    'relative_path' => 'app/NewFile.php',
                ]],
            ]),
            'sha256'
        );
    }

    public function test_rejects_missing_sha256_for_replace(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                ]],
            ]),
            'sha256'
        );
    }

    public function test_rejects_invalid_sha256_format(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => 'not-a-valid-sha',
                ]],
            ]),
            'sha256'
        );
    }

    public function test_delete_operation_does_not_require_payload_sha256(): void
    {
        $result = $this->validator->validate(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'delete',
                    'relative_path' => 'resources/views/old.blade.php',
                ]],
            ])
        );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );
    }

    public function test_rejects_migration_outside_migration_directory(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'migrations' => [[
                    'migration_name' => '2026_08_24_100000_bad.php',
                    'relative_path' => 'app/bad.php',
                    'sha256' => str_repeat('b', 64),
                ]],
            ]),
            'migration'
        );
    }

    public function test_rejects_non_php_migration_file(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'migrations' => [[
                    'migration_name' => '2026_08_24_100000_bad.txt',
                    'relative_path' => 'database/migrations/2026_08_24_100000_bad.txt',
                    'sha256' => str_repeat('b', 64),
                ]],
            ]),
            'migration'
        );
    }

    public function test_rejects_invalid_migration_sha256(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'migrations' => [[
                    'migration_name' => '2026_08_24_100000_good.php',
                    'relative_path' => 'database/migrations/2026_08_24_100000_good.php',
                    'sha256' => 'bad',
                ]],
            ]),
            'sha256'
        );
    }

    public function test_rejects_duplicate_migration_identity(): void
    {
        $migration = [
            'migration_name' => '2026_08_24_100000_good.php',
            'relative_path' => 'database/migrations/2026_08_24_100000_good.php',
            'sha256' => str_repeat('b', 64),
        ];

        $this->assertRejected(
            $this->baseManifest([
                'migrations' => [
                    $migration,
                    $migration,
                ],
            ]),
            'duplicate'
        );
    }

    public function test_module_scope_requires_module_identifier(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'module' => '',
            ]),
            'module'
        );
    }

    public function test_feature_scope_requires_module_and_feature_identifier(): void
    {
        $this->assertRejected(
            $this->baseManifest([
                'scope' => 'feature',
                'module' => '',
                'feature' => '',
            ]),
            'feature'
        );
    }

    public function test_safe_module_manifest_is_accepted(): void
    {
        $result = $this->validator->validate(
            $this->baseManifest([
                'files' => [[
                    'operation' => 'replace',
                    'relative_path' =>
                        'resources/views/po_suppliers/edit.blade.php',
                    'sha256' => str_repeat('a', 64),
                ]],
                'migrations' => [[
                    'migration_name' =>
                        '2026_08_24_100000_add_example_field.php',
                    'relative_path' =>
                        'database/migrations/2026_08_24_100000_add_example_field.php',
                    'sha256' => str_repeat('b', 64),
                ]],
            ])
        );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );
    }
}