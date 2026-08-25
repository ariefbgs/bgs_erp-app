<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentBackupManager;
use App\Services\Deployment\DeploymentPackageManifest;
use PHPUnit\Framework\TestCase;

class DeploymentBackupContractTest extends TestCase
{
    public function test_backup_manager_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentBackupManager::class),
            'DeploymentBackupManager contract is not implemented.'
        );
    }

    public function test_backup_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentBackupResult::class
            ),
            'DeploymentBackupResult contract is not implemented.'
        );
    }

    public function test_backup_manager_exposes_backup_method(): void
    {
        $class = DeploymentBackupManager::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentBackupManager contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'backup'),
            'DeploymentBackupManager::backup() is required.'
        );
    }

    public function test_backup_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentBackupResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentBackupResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'recoveryPath',
            'files',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentBackupResult::{$method}() is required."
            );
        }
    }

    public function test_replace_existing_file_is_backed_up(): void
    {
        $this->requireContract();

        $appRoot = $this->applicationRoot('replace');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            '<?php echo "OLD";'
        );

        $recovery =
            $this->recoveryRoot('replace');

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertFileExists(
            $recovery
            .DIRECTORY_SEPARATOR
            .'files'
            .DIRECTORY_SEPARATOR
            .'app'
            .DIRECTORY_SEPARATOR
            .'Test.php'
        );

        $this->assertSame(
            '<?php echo "OLD";',
            file_get_contents(
                $recovery
                .DIRECTORY_SEPARATOR
                .'files'
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_delete_existing_file_is_backed_up(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('delete');

        $this->writeApplicationFile(
            $appRoot,
            'resources/views/old.blade.php',
            'OLD VIEW'
        );

        $recovery =
            $this->recoveryRoot('delete');

        $manifest = $this->manifest([
            [
                'operation' => 'delete',
                'relative_path' =>
                    'resources/views/old.blade.php',
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertFileExists(
            $recovery
            .DIRECTORY_SEPARATOR
            .'files'
            .DIRECTORY_SEPARATOR
            .'resources'
            .DIRECTORY_SEPARATOR
            .'views'
            .DIRECTORY_SEPARATOR
            .'old.blade.php'
        );
    }

    public function test_add_new_file_does_not_require_source_backup(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('add-new');

        $recovery =
            $this->recoveryRoot('add-new');

        $manifest = $this->manifest([
            [
                'operation' => 'add',
                'relative_path' => 'app/NewFile.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertCount(
            0,
            $result->files()
        );
    }

    public function test_replace_missing_source_file_is_rejected(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('replace-missing');

        $recovery =
            $this->recoveryRoot('replace-missing');

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' => 'app/Missing.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertStringContainsString(
            'missing',
            strtolower(
                implode(' | ', $result->errors())
            )
        );
    }

    public function test_delete_missing_source_file_is_rejected(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('delete-missing');

        $recovery =
            $this->recoveryRoot('delete-missing');

        $manifest = $this->manifest([
            [
                'operation' => 'delete',
                'relative_path' => 'app/Missing.php',
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertFalse(
            $result->isValid()
        );
    }

    public function test_backup_preserves_relative_paths(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('relative-path');

        $this->writeApplicationFile(
            $appRoot,
            'resources/views/po_suppliers/edit.blade.php',
            'OLD EDIT'
        );

        $recovery =
            $this->recoveryRoot('relative-path');

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' =>
                    'resources/views/po_suppliers/edit.blade.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertFileExists(
            $recovery
            .DIRECTORY_SEPARATOR
            .'files'
            .DIRECTORY_SEPARATOR
            .'resources'
            .DIRECTORY_SEPARATOR
            .'views'
            .DIRECTORY_SEPARATOR
            .'po_suppliers'
            .DIRECTORY_SEPARATOR
            .'edit.blade.php'
        );
    }

    public function test_existing_non_empty_recovery_directory_is_rejected(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('existing-recovery');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $recovery =
            $this->recoveryRoot('existing-recovery');

        mkdir(
            $recovery,
            0777,
            true
        );

        file_put_contents(
            $recovery
            .DIRECTORY_SEPARATOR
            .'existing.txt',
            'DO NOT OVERWRITE'
        );

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertFalse(
            $result->isValid()
        );
    }

    public function test_backup_result_contains_checksum_metadata(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('checksum');

        $content =
            '<?php echo "ORIGINAL";';

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            $content
        );

        $recovery =
            $this->recoveryRoot('checksum');

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' => 'app/Test.php',
                'sha256' => str_repeat('a', 64),
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $files =
            $result->files();

        $this->assertCount(
            1,
            $files
        );

        $this->assertSame(
            'app/Test.php',
            $files[0]['relative_path']
        );

        $this->assertSame(
            hash('sha256', $content),
            $files[0]['sha256']
        );
    }

    public function test_multiple_existing_files_are_backed_up_as_one_recovery_set(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('multi');

        $this->writeApplicationFile(
            $appRoot,
            'app/A.php',
            'A-OLD'
        );

        $this->writeApplicationFile(
            $appRoot,
            'resources/views/B.blade.php',
            'B-OLD'
        );

        $recovery =
            $this->recoveryRoot('multi');

        $manifest = $this->manifest([
            [
                'operation' => 'replace',
                'relative_path' => 'app/A.php',
                'sha256' => str_repeat('a', 64),
            ],
            [
                'operation' => 'delete',
                'relative_path' =>
                    'resources/views/B.blade.php',
            ],
        ]);

        $result = (new DeploymentBackupManager())
            ->backup(
                $appRoot,
                $recovery,
                $manifest
            );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertCount(
            2,
            $result->files()
        );
    }

    private function requireContract(): void
    {
        if (!class_exists(DeploymentBackupManager::class)) {
            $this->fail(
                'DeploymentBackupManager contract is not implemented.'
            );
        }
    }

    private function manifest(
        array $files
    ): DeploymentPackageManifest {
        return new DeploymentPackageManifest(
            'BGS-BACKUP-001',
            '1.0.0',
            'module',
            'test',
            null,
            $files,
            []
        );
    }

    private function applicationRoot(
        string $suffix
    ): string {
        $path =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-backup-app-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $path,
            0777,
            true
        );

        return $path;
    }

    private function recoveryRoot(
        string $suffix
    ): string {
        return
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-backup-recovery-'
            .$suffix
            .'-'
            .uniqid('', true);
    }

    private function writeApplicationFile(
        string $appRoot,
        string $relativePath,
        string $content
    ): void {
        $target =
            $appRoot
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        $directory =
            dirname($target);

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