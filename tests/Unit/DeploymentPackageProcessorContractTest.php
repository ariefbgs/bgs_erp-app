<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentPackageProcessor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class DeploymentPackageProcessorContractTest extends TestCase
{
    public function test_processor_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentPackageProcessor::class),
            'DeploymentPackageProcessor contract is not implemented.'
        );
    }

    public function test_process_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentPackageProcessResult::class
            ),
            'DeploymentPackageProcessResult contract is not implemented.'
        );
    }

    public function test_processor_exposes_process_method(): void
    {
        $class = DeploymentPackageProcessor::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageProcessor contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'process'),
            'DeploymentPackageProcessor::process() is required.'
        );
    }

    public function test_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentPackageProcessResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageProcessResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'manifest',
            'stagingPath',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentPackageProcessResult::{$method}() is required."
            );
        }
    }

    public function test_invalid_zip_is_rejected_end_to_end(): void
    {
        $this->requireContract();

        $zip =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-processor-invalid-'
            .uniqid('', true)
            .'.zip';

        file_put_contents(
            $zip,
            'NOT A ZIP'
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('invalid')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_malformed_manifest_json_is_rejected(): void
    {
        $this->requireContract();

        $zip = $this->createZip(
            'malformed-json',
            [
                'manifest.json' => '{ invalid json',
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('malformed-json')
            );

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'json',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_scalar_manifest_json_is_rejected(): void
    {
        $this->requireContract();

        $zip = $this->createZip(
            'scalar-json',
            [
                'manifest.json' => '"hello"',
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('scalar-json')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_manifest_security_failure_is_propagated(): void
    {
        $this->requireContract();

        $content = '<?php return true;';

        $zip = $this->createZip(
            'security-failure',
            [
                'manifest.json' => json_encode([
                    'release_id' => 'BGS-N-001',
                    'version' => '1.0.0',
                    'scope' => 'module',
                    'module' => 'po_supplier',
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => '../.env',
                        'sha256' => hash('sha256', $content),
                    ]],
                    'migrations' => [],
                ]),
                'payload/app/Test.php' => $content,
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('security-failure')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_missing_payload_is_rejected_end_to_end(): void
    {
        $this->requireContract();

        $zip = $this->createZip(
            'missing-payload',
            [
                'manifest.json' => json_encode([
                    'release_id' => 'BGS-N-002',
                    'version' => '1.0.0',
                    'scope' => 'module',
                    'module' => 'po_supplier',
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => 'app/Test.php',
                        'sha256' => str_repeat('a', 64),
                    ]],
                    'migrations' => [],
                ]),
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('missing-payload')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_checksum_mismatch_is_rejected_end_to_end(): void
    {
        $this->requireContract();

        $content = '<?php return true;';

        $zip = $this->createZip(
            'checksum-mismatch',
            [
                'manifest.json' => json_encode([
                    'release_id' => 'BGS-N-003',
                    'version' => '1.0.0',
                    'scope' => 'module',
                    'module' => 'po_supplier',
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => 'app/Test.php',
                        'sha256' => str_repeat('a', 64),
                    ]],
                    'migrations' => [],
                ]),
                'payload/app/Test.php' => $content,
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('checksum-mismatch')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_unlisted_payload_is_rejected_end_to_end(): void
    {
        $this->requireContract();

        $content = '<?php return true;';

        $zip = $this->createZip(
            'unlisted',
            [
                'manifest.json' => json_encode([
                    'release_id' => 'BGS-N-004',
                    'version' => '1.0.0',
                    'scope' => 'module',
                    'module' => 'po_supplier',
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => 'app/Test.php',
                        'sha256' => hash('sha256', $content),
                    ]],
                    'migrations' => [],
                ]),
                'payload/app/Test.php' => $content,
                'payload/app/Unexpected.php' =>
                    '<?php echo "NO";',
            ]
        );

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $this->staging('unlisted')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_safe_package_is_accepted_end_to_end(): void
    {
        $this->requireContract();

        $fileContent =
            '<?php echo "SAFE";';

        $migrationContent =
            '<?php return true;';

        $migrationName =
            '2026_08_24_120000_test.php';

        $zip = $this->createZip(
            'safe',
            [
                'manifest.json' => json_encode([
                    'release_id' => 'BGS-N-005',
                    'version' => '1.0.1',
                    'scope' => 'module',
                    'module' => 'po_supplier',
                    'files' => [[
                        'operation' => 'replace',
                        'relative_path' => 'app/Test.php',
                        'sha256' => hash(
                            'sha256',
                            $fileContent
                        ),
                    ]],
                    'migrations' => [[
                        'migration_name' => $migrationName,
                        'relative_path' =>
                            'database/migrations/'.$migrationName,
                        'sha256' => hash(
                            'sha256',
                            $migrationContent
                        ),
                    ]],
                ]),
                'payload/app/Test.php' =>
                    $fileContent,
                'payload/database/migrations/'.$migrationName =>
                    $migrationContent,
            ]
        );

        $staging =
            $this->staging('safe');

        $result = (new DeploymentPackageProcessor())
            ->process(
                $zip,
                $staging
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertNotNull(
            $result->manifest()
        );

        $this->assertSame(
            'BGS-N-005',
            $result->manifest()->releaseId()
        );

        $this->assertSame(
            $staging,
            $result->stagingPath()
        );
    }

    private function requireContract(): void
    {
        if (!class_exists(DeploymentPackageProcessor::class)) {
            $this->fail(
                'DeploymentPackageProcessor contract is not implemented.'
            );
        }
    }

    private function createZip(
        string $suffix,
        array $entries
    ): string {
        $zipPath =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-processor-'
            .$suffix
            .'-'
            .uniqid('', true)
            .'.zip';

        $zip = new ZipArchive();

        $opened = $zip->open(
            $zipPath,
            ZipArchive::CREATE
            | ZipArchive::OVERWRITE
        );

        $this->assertTrue(
            $opened === true,
            'Unable to create processor ZIP fixture.'
        );

        foreach ($entries as $name => $content) {
            $zip->addFromString(
                $name,
                $content
            );
        }

        $zip->close();

        return $zipPath;
    }

    private function staging(
        string $suffix
    ): string {
        return
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-processor-staging-'
            .$suffix
            .'-'
            .uniqid('', true);
    }
}