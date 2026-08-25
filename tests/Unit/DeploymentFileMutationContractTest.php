<?php

namespace Tests\Unit;

use App\Services\Deployment\DeploymentBackupManager;
use App\Services\Deployment\DeploymentBackupResult;
use App\Services\Deployment\DeploymentFileMutator;
use App\Services\Deployment\DeploymentPackageManifest;
use PHPUnit\Framework\TestCase;

class DeploymentFileMutationContractTest extends TestCase
{
    public function test_file_mutator_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(DeploymentFileMutator::class),
            'DeploymentFileMutator contract is not implemented.'
        );
    }

    public function test_file_mutation_result_contract_exists(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Services\Deployment\DeploymentFileMutationResult::class
            ),
            'DeploymentFileMutationResult contract is not implemented.'
        );
    }

    public function test_mutator_exposes_mutate_method(): void
    {
        $class = DeploymentFileMutator::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentFileMutator contract is not implemented.'
            );
        }

        $this->assertTrue(
            method_exists($class, 'mutate'),
            'DeploymentFileMutator::mutate() is required.'
        );
    }

    public function test_mutation_result_exposes_safe_contract(): void
    {
        $class =
            \App\Services\Deployment\DeploymentFileMutationResult::class;

        if (!class_exists($class)) {
            $this->fail(
                'DeploymentFileMutationResult contract is not implemented.'
            );
        }

        foreach ([
            'isValid',
            'errors',
            'files',
        ] as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                "DeploymentFileMutationResult::{$method}() is required."
            );
        }
    }

    public function test_add_new_file_is_applied(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('add');

        $staging =
            $this->stagingRoot('add');

        $recovery =
            $this->recoveryRoot('add');

        $content =
            '<?php echo "NEW";';

        $this->writePayload(
            $staging,
            'app/New.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'add',
                    'relative_path' => 'app/New.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertSame(
            $content,
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'New.php'
            )
        );
    }

    public function test_add_rejects_existing_target(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('add-existing');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'ORIGINAL'
        );

        $staging =
            $this->stagingRoot('add-existing');

        $content =
            'NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'add',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $backup =
            DeploymentBackupResult::valid(
                $this->recoveryRoot('unused'),
                []
            );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'ORIGINAL',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_replace_requires_valid_backup_prerequisite(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('replace-no-backup');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $staging =
            $this->stagingRoot('replace-no-backup');

        $content =
            'NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $backup =
            DeploymentBackupResult::invalid(
                ['backup unavailable'],
                $this->recoveryRoot('invalid')
            );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_replace_existing_file_is_applied_after_backup(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('replace');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $staging =
            $this->stagingRoot('replace');

        $content =
            'NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $recovery =
            $this->recoveryRoot('replace');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertSame(
            'NEW',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );

        $this->assertSame(
            'OLD',
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

    public function test_delete_existing_file_is_applied_after_backup(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('delete');

        $this->writeApplicationFile(
            $appRoot,
            'resources/views/old.blade.php',
            'OLD VIEW'
        );

        $staging =
            $this->stagingRoot('delete');

        $manifest =
            $this->manifest([
                [
                    'operation' => 'delete',
                    'relative_path' =>
                        'resources/views/old.blade.php',
                ],
            ]);

        $recovery =
            $this->recoveryRoot('delete');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertTrue(
            $result->isValid(),
            implode(' | ', $result->errors())
        );

        $this->assertFileDoesNotExist(
            $appRoot
            .DIRECTORY_SEPARATOR
            .'resources'
            .DIRECTORY_SEPARATOR
            .'views'
            .DIRECTORY_SEPARATOR
            .'old.blade.php'
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

    public function test_replace_rejects_missing_backup_metadata(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('missing-meta');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $staging =
            $this->stagingRoot('missing-meta');

        $content =
            'NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $backup =
            DeploymentBackupResult::valid(
                $this->recoveryRoot('missing-meta'),
                []
            );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_tampered_backup_is_rejected_before_mutation(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('tampered-backup');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $staging =
            $this->stagingRoot('tampered-backup');

        $content =
            'NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $recovery =
            $this->recoveryRoot('tampered-backup');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        file_put_contents(
            $recovery
            .DIRECTORY_SEPARATOR
            .'files'
            .DIRECTORY_SEPARATOR
            .'app'
            .DIRECTORY_SEPARATOR
            .'Test.php',
            'TAMPERED'
        );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_tampered_payload_is_rejected_before_mutation(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('tampered-payload');

        $this->writeApplicationFile(
            $appRoot,
            'app/Test.php',
            'OLD'
        );

        $staging =
            $this->stagingRoot('tampered-payload');

        $expected =
            'EXPECTED NEW';

        $this->writePayload(
            $staging,
            'app/Test.php',
            'TAMPERED NEW'
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/Test.php',
                    'sha256' => hash(
                        'sha256',
                        $expected
                    ),
                ],
            ]);

        $recovery =
            $this->recoveryRoot('tampered-payload');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'Test.php'
            )
        );
    }

    public function test_multi_file_preflight_failure_causes_no_partial_mutation(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('atomic-preflight');

        $this->writeApplicationFile(
            $appRoot,
            'app/A.php',
            'A-OLD'
        );

        $this->writeApplicationFile(
            $appRoot,
            'app/B.php',
            'B-OLD'
        );

        $staging =
            $this->stagingRoot('atomic-preflight');

        $this->writePayload(
            $staging,
            'app/A.php',
            'A-NEW'
        );

        /*
         * B payload intentionally absent.
         */

        $manifest =
            $this->manifest([
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/A.php',
                    'sha256' => hash(
                        'sha256',
                        'A-NEW'
                    ),
                ],
                [
                    'operation' => 'replace',
                    'relative_path' => 'app/B.php',
                    'sha256' => hash(
                        'sha256',
                        'B-NEW'
                    ),
                ],
            ]);

        $recovery =
            $this->recoveryRoot('atomic-preflight');

        $backup =
            (new DeploymentBackupManager())
                ->backup(
                    $appRoot,
                    $recovery,
                    $manifest
                );

        $this->assertTrue(
            $backup->isValid()
        );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertFalse(
            $result->isValid()
        );

        $this->assertSame(
            'A-OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'A.php'
            )
        );

        $this->assertSame(
            'B-OLD',
            file_get_contents(
                $appRoot
                .DIRECTORY_SEPARATOR
                .'app'
                .DIRECTORY_SEPARATOR
                .'B.php'
            )
        );
    }

    public function test_mutation_result_contains_applied_file_metadata(): void
    {
        $this->requireContract();

        $appRoot =
            $this->applicationRoot('metadata');

        $staging =
            $this->stagingRoot('metadata');

        $content =
            'NEW FILE';

        $this->writePayload(
            $staging,
            'app/New.php',
            $content
        );

        $manifest =
            $this->manifest([
                [
                    'operation' => 'add',
                    'relative_path' => 'app/New.php',
                    'sha256' => hash(
                        'sha256',
                        $content
                    ),
                ],
            ]);

        $backup =
            DeploymentBackupResult::valid(
                $this->recoveryRoot('metadata'),
                []
            );

        $result =
            (new DeploymentFileMutator())
                ->mutate(
                    $appRoot,
                    $staging,
                    $manifest,
                    $backup
                );

        $this->assertTrue(
            $result->isValid()
        );

        $files =
            $result->files();

        $this->assertCount(
            1,
            $files
        );

        $this->assertSame(
            'add',
            $files[0]['operation']
        );

        $this->assertSame(
            'app/New.php',
            $files[0]['relative_path']
        );

        $this->assertSame(
            hash('sha256', $content),
            $files[0]['sha256']
        );
    }

    private function requireContract(): void
    {
        if (!class_exists(DeploymentFileMutator::class)) {
            $this->fail(
                'DeploymentFileMutator contract is not implemented.'
            );
        }
    }

    private function manifest(
        array $files
    ): DeploymentPackageManifest {
        return new DeploymentPackageManifest(
            'BGS-MUTATION-001',
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
        return $this->makeRoot(
            'app',
            $suffix
        );
    }

    private function stagingRoot(
        string $suffix
    ): string {
        $root =
            $this->makeRoot(
                'staging',
                $suffix
            );

        mkdir(
            $root
            .DIRECTORY_SEPARATOR
            .'payload',
            0777,
            true
        );

        return $root;
    }

    private function recoveryRoot(
        string $suffix
    ): string {
        return
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-mutation-recovery-'
            .$suffix
            .'-'
            .uniqid('', true);
    }

    private function makeRoot(
        string $type,
        string $suffix
    ): string {
        $root =
            sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'bgs-mutation-'
            .$type
            .'-'
            .$suffix
            .'-'
            .uniqid('', true);

        mkdir(
            $root,
            0777,
            true
        );

        return $root;
    }

    private function writeApplicationFile(
        string $root,
        string $relative,
        string $content
    ): void {
        $this->writeAt(
            $root,
            $relative,
            $content
        );
    }

    private function writePayload(
        string $staging,
        string $relative,
        string $content
    ): void {
        $this->writeAt(
            $staging
            .DIRECTORY_SEPARATOR
            .'payload',
            $relative,
            $content
        );
    }

    private function writeAt(
        string $root,
        string $relative,
        string $content
    ): void {
        $target =
            $root
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relative
            );

        if (!is_dir(dirname($target))) {
            mkdir(
                dirname($target),
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