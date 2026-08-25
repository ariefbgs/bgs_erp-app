<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentPackageInspector;
use App\Services\Deployment\DeploymentPackageManifest;
use PHPUnit\Framework\TestCase;

class DeploymentPackagePayloadContractTest extends TestCase
{
    public function test_package_inspector_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentPackageInspector::class),
            'DeploymentPackageInspector contract is not implemented.'
        );
    }

    public function test_package_inspection_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentPackageInspectionResult::class
            ),
            'DeploymentPackageInspectionResult contract is not implemented.'
        );
    }

    public function test_inspector_exposes_inspect_method(): void
    {
        $class = DeploymentPackageInspector::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageInspector contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'inspect'),
            'DeploymentPackageInspector::inspect() is required.'
        );
    }

    public function test_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentPackageInspectionResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageInspectionResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'packageRoot',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentPackageInspectionResult::{$method}() is required."
            );
        }
    }

    public function test_missing_add_payload_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('missing-add');

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-001',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'add',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'missing',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_missing_replace_payload_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('missing-replace');

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-002',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());
    }

    public function test_delete_operation_does_not_require_payload_file(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('delete-only');

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-003',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'delete',
                'relative_path' =>
                    'resources/views/legacy.blade.php',
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );
    }

    public function test_file_checksum_mismatch_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('bad-checksum');

        $this->writePayload(
            $root,
            'app/Test.php',
            '<?php echo "SAFE";'
        );

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-004',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'checksum',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_valid_file_checksum_is_accepted(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('good-checksum');

        $content = '<?php echo "SAFE";';

        $this->writePayload(
            $root,
            'app/Test.php',
            $content
        );

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-005',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => hash('sha256', $content),
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );
    }

    public function test_missing_migration_payload_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('missing-migration');

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-006',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [],
            [[
                'migration_name' =>
                    '2026_08_24_100000_test.php',
                'relative_path' =>
                    'database/migrations/2026_08_24_100000_test.php',
                'sha256' => str_repeat('b', 64),
            ]]
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());
    }

    public function test_migration_checksum_mismatch_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('migration-bad-checksum');

        $this->writePayload(
            $root,
            'database/migrations/2026_08_24_100000_test.php',
            '<?php return true;'
        );

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-007',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [],
            [[
                'migration_name' =>
                    '2026_08_24_100000_test.php',
                'relative_path' =>
                    'database/migrations/2026_08_24_100000_test.php',
                'sha256' => str_repeat('b', 64),
            ]]
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());
    }

    public function test_unlisted_payload_is_rejected(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('unlisted');

        $this->writePayload(
            $root,
            'app/Test.php',
            '<?php echo "SAFE";'
        );

        $this->writePayload(
            $root,
            'app/Unexpected.php',
            '<?php echo "UNLISTED";'
        );

        $content = '<?php echo "SAFE";';

        file_put_contents(
            $root . '/payload/app/Test.php',
            $content
        );

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-008',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => hash('sha256', $content),
            ]],
            []
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'unlisted',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_safe_package_payload_is_accepted(): void
    {
        $this->requireContract();

        $root = $this->tempPackageRoot('safe-package');

        $fileContent = '<?php echo "SAFE";';

        $migrationContent = '<?php return true;';

        $this->writePayload(
            $root,
            'app/Test.php',
            $fileContent
        );

        $this->writePayload(
            $root,
            'database/migrations/2026_08_24_100000_test.php',
            $migrationContent
        );

        $manifest = new DeploymentPackageManifest(
            'BGS-PAYLOAD-009',
            '1.0.0',
            'module',
            'po_supplier',
            null,
            [[
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => hash('sha256', $fileContent),
            ]],
            [[
                'migration_name' =>
                    '2026_08_24_100000_test.php',
                'relative_path' =>
                    'database/migrations/2026_08_24_100000_test.php',
                'sha256' => hash('sha256', $migrationContent),
            ]]
        );

        $result = (new DeploymentPackageInspector())
            ->inspect($root, $manifest);

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );
    }

    private function requireContract(): void
    {
        if (!class_exists(DeploymentPackageInspector::class)) {
            $this->fail(
                'DeploymentPackageInspector contract is not implemented.'
            );
        }
    }

    private function tempPackageRoot(string $suffix): string
    {
        $root =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-deployment-package-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $root . DIRECTORY_SEPARATOR . 'payload',
            0777,
            true
        );

        return $root;
    }

    private function writePayload(
        string $root,
        string $relativePath,
        string $content
    ): void {
        $target =
            $root
            .DIRECTORY_SEPARATOR
            .'payload'
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        $directory = dirname($target);

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0777,
                true
            );
        }

        file_put_contents(
            $target,
            $content
        );
    }
}