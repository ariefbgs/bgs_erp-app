<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentPackageExtractor;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class DeploymentPackageZipSecurityContractTest extends TestCase
{
    public function test_extractor_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentPackageExtractor::class),
            'DeploymentPackageExtractor contract is not implemented.'
        );
    }

    public function test_extraction_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentPackageExtractionResult::class
            ),
            'DeploymentPackageExtractionResult contract is not implemented.'
        );
    }

    public function test_extractor_exposes_extract_method(): void
    {
        $class = DeploymentPackageExtractor::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageExtractor contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'extract'),
            'DeploymentPackageExtractor::extract() is required.'
        );
    }

    public function test_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentPackageExtractionResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentPackageExtractionResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'stagingPath',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentPackageExtractionResult::{$method}() is required."
            );
        }
    }

    public function test_invalid_zip_is_rejected(): void
    {
        $this->requireContract();

        $zipPath =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-invalid-'
            .uniqid('', true)
            .'.zip';

        file_put_contents(
            $zipPath,
            'NOT A ZIP'
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('invalid')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_missing_manifest_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'missing-manifest',
            [
                'payload/app/Test.php' => '<?php return true;',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('missing-manifest')
            );

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'manifest',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_parent_directory_zip_slip_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'zip-slip-parent',
            [
                'manifest.json' => '{}',
                '../outside.php' => '<?php echo "BAD";',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('zip-slip-parent')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_nested_parent_directory_zip_slip_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'zip-slip-nested',
            [
                'manifest.json' => '{}',
                'payload/app/../../outside.php' =>
                    '<?php echo "BAD";',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('zip-slip-nested')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_linux_absolute_entry_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'linux-absolute',
            [
                'manifest.json' => '{}',
                '/etc/passwd' => 'BAD',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('linux-absolute')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_windows_absolute_entry_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'windows-absolute',
            [
                'manifest.json' => '{}',
                'C:\\Windows\\system.ini' => 'BAD',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('windows-absolute')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_backslash_traversal_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'backslash-traversal',
            [
                'manifest.json' => '{}',
                'payload\\..\\..\\outside.php' =>
                    '<?php echo "BAD";',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('backslash-traversal')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_entry_outside_allowed_package_roots_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->createZip(
            'unexpected-root',
            [
                'manifest.json' => '{}',
                'README.txt' => 'UNLISTED',
            ]
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('unexpected-root')
            );

        $this->assertFalse($result->isValid());

        $this->assertStringContainsString(
            'entry',
            strtolower(implode(' | ', $result->errors()))
        );
    }

    public function test_existing_non_empty_staging_directory_is_rejected(): void
    {
        $this->requireContract();

        $zipPath = $this->safeZip('existing-staging');

        $staging =
            $this->newStagingPath('existing-staging');

        mkdir(
            $staging,
            0777,
            true
        );

        file_put_contents(
            $staging . DIRECTORY_SEPARATOR . 'existing.txt',
            'DO NOT OVERWRITE'
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $staging
            );

        $this->assertFalse($result->isValid());
    }

    public function test_excessive_entry_count_is_rejected(): void
    {
        $this->requireContract();

        $entries = [
            'manifest.json' => '{}',
        ];

        for ($i = 0; $i < 1100; $i++) {
            $entries[
                'payload/files/file-'.$i.'.txt'
            ] = 'x';
        }

        $zipPath = $this->createZip(
            'too-many-entries',
            $entries
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('too-many-entries')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_excessive_uncompressed_size_is_rejected(): void
    {
        $this->requireContract();

        /*
         * Contract expects extractor to cap total uncompressed
         * package content. Implementation may choose the exact
         * constant, but this fixture intentionally exceeds 100 MB.
         */
        $largeChunk = str_repeat('A', 1024 * 1024);

        $entries = [
            'manifest.json' => '{}',
        ];

        for ($i = 0; $i < 105; $i++) {
            $entries[
                'payload/large/file-'.$i.'.bin'
            ] = $largeChunk;
        }

        $zipPath = $this->createZip(
            'too-large',
            $entries
        );

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $this->newStagingPath('too-large')
            );

        $this->assertFalse($result->isValid());
    }

    public function test_safe_zip_is_accepted(): void
    {
        $this->requireContract();

        $zipPath = $this->safeZip('safe');

        $staging =
            $this->newStagingPath('safe');

        $result = (new DeploymentPackageExtractor())
            ->extract(
                $zipPath,
                $staging
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertFileExists(
            $staging
            .DIRECTORY_SEPARATOR
            .'manifest.json'
        );

        $this->assertFileExists(
            $staging
            .DIRECTORY_SEPARATOR
            .'payload'
            .DIRECTORY_SEPARATOR
            .'app'
            .DIRECTORY_SEPARATOR
            .'Test.php'
        );
    }

    private function requireContract(): void
    {
        if (!class_exists(DeploymentPackageExtractor::class)) {
            $this->fail(
                'DeploymentPackageExtractor contract is not implemented.'
            );
        }
    }

    private function safeZip(string $suffix): string
    {
        return $this->createZip(
            $suffix,
            [
                'manifest.json' => json_encode(
                    [
                        'release_id' => 'BGS-ZIP-001',
                        'version' => '1.0.0',
                        'scope' => 'module',
                        'module' => 'po_supplier',
                        'files' => [],
                        'migrations' => [],
                    ],
                    JSON_PRETTY_PRINT
                ),
                'payload/app/Test.php' =>
                    '<?php return true;',
            ]
        );
    }

    private function createZip(
        string $suffix,
        array $entries
    ): string {
        $zipPath =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-zip-contract-'
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
            'Unable to create ZIP test fixture.'
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

    private function newStagingPath(
        string $suffix
    ): string {
        return
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-zip-staging-'
            .$suffix
            .'-'
            .uniqid('', true);
    }
}