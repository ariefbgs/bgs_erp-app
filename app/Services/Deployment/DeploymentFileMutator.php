<?php

namespace App\Services\Deployment;

use RuntimeException;
use Throwable;

final class DeploymentFileMutator
{
    private DeploymentFileOperationExecutorInterface $executor;

    public function __construct(
        ?DeploymentFileOperationExecutorInterface $executor = null
    ) {
        $this->executor =
            $executor
            ?? new LocalDeploymentFileOperationExecutor();
    }

    public function mutate(
        string $applicationRoot,
        string $stagingPath,
        DeploymentPackageManifest $manifest,
        DeploymentBackupResult $backup
    ): DeploymentFileMutationResult {

        $applicationRoot =
            $this->normalizeRoot(
                $applicationRoot
            );

        $stagingPath =
            $this->normalizeRoot(
                $stagingPath
            );

        if (!is_dir($applicationRoot)) {
            return DeploymentFileMutationResult::invalid(
                ['Application root does not exist.']
            );
        }

        $payloadRoot =
            $stagingPath
            .DIRECTORY_SEPARATOR
            .'payload';

        if (!is_dir($payloadRoot)) {
            return DeploymentFileMutationResult::invalid(
                ['Staging payload directory does not exist.']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BACKUP METADATA MAP
        |--------------------------------------------------------------------------
        */

        $backupMap = [];

        foreach ($backup->files() as $file) {
            if (!is_array($file)) {
                continue;
            }

            $relativePath =
                (string) (
                    $file['relative_path']
                    ?? ''
                );

            if ($relativePath === '') {
                continue;
            }

            $backupMap[
                strtolower(
                    $this->normalizeRelativePath(
                        $relativePath
                    )
                )
            ] = $file;
        }


        /*
        |--------------------------------------------------------------------------
        | FULL PREFLIGHT
        |--------------------------------------------------------------------------
        |
        | No application mutation is allowed until ALL operations pass.
        |--------------------------------------------------------------------------
        */

        $operations = [];
        $needsBackup = false;

        foreach (
            $manifest->files()
            as $index => $file
        ) {

            if (!is_array($file)) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "files.{$index} is invalid.",
                    ]
                );
            }

            $operation =
                strtolower(
                    trim(
                        (string) (
                            $file['operation']
                            ?? ''
                        )
                    )
                );

            $relativePath =
                $this->normalizeRelativePath(
                    (string) (
                        $file['relative_path']
                        ?? ''
                    )
                );

            $expectedSha =
                strtolower(
                    trim(
                        (string) (
                            $file['sha256']
                            ?? ''
                        )
                    )
                );

            if (
                !in_array(
                    $operation,
                    [
                        'add',
                        'replace',
                        'delete',
                    ],
                    true
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "files.{$index}.operation is invalid.",
                    ]
                );
            }

            if (
                !$this->isSafeRelativePath(
                    $relativePath
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "files.{$index}.relative_path is unsafe.",
                    ]
                );
            }

            $targetPath =
                $this->joinRelativePath(
                    $applicationRoot,
                    $relativePath
                );

            if (
                !$this->pathInsideRoot(
                    $targetPath,
                    $applicationRoot
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "files.{$index} target escaped application root.",
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | ADD PREFLIGHT
            |--------------------------------------------------------------------------
            */

            if ($operation === 'add') {

                if (
                    file_exists($targetPath)
                    || is_link($targetPath)
                ) {
                    return DeploymentFileMutationResult::invalid(
                        [
                            "Add target already exists: {$relativePath}",
                        ]
                    );
                }

                $payloadPath =
                    $this->joinRelativePath(
                        $payloadRoot,
                        $relativePath
                    );

                $payloadError =
                    $this->verifyPayload(
                        $payloadPath,
                        $expectedSha,
                        $relativePath
                    );

                if ($payloadError !== null) {
                    return DeploymentFileMutationResult::invalid(
                        [$payloadError]
                    );
                }

                $operations[] = [
                    'operation' =>
                        $operation,

                    'relative_path' =>
                        $relativePath,

                    'target_path' =>
                        $targetPath,

                    'payload_path' =>
                        $payloadPath,

                    'sha256' =>
                        $expectedSha,
                ];

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | REPLACE / DELETE REQUIRE BACKUP
            |--------------------------------------------------------------------------
            */

            $needsBackup = true;

            if (!$backup->isValid()) {
                return DeploymentFileMutationResult::invalid(
                    [
                        'Valid recovery backup is required before '
                        .$operation
                        .' operation.',
                    ]
                );
            }

            if (!is_file($targetPath)) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Application source file is missing: {$relativePath}",
                    ]
                );
            }

            $backupKey =
                strtolower(
                    $relativePath
                );

            if (
                !isset(
                    $backupMap[$backupKey]
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Recovery metadata is missing: {$relativePath}",
                    ]
                );
            }

            $backupMetadata =
                $backupMap[$backupKey];

            $backupSha =
                strtolower(
                    trim(
                        (string) (
                            $backupMetadata['sha256']
                            ?? ''
                        )
                    )
                );

            if (
                !preg_match(
                    '/^[a-f0-9]{64}$/',
                    $backupSha
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Recovery checksum metadata is invalid: {$relativePath}",
                    ]
                );
            }

            $backupFile =
                $this->joinRelativePath(
                    rtrim(
                        $backup->recoveryPath(),
                        DIRECTORY_SEPARATOR
                    )
                    .DIRECTORY_SEPARATOR
                    .'files',
                    $relativePath
                );

            if (!is_file($backupFile)) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Recovery file is missing: {$relativePath}",
                    ]
                );
            }

            $actualBackupSha =
                hash_file(
                    'sha256',
                    $backupFile
                );

            if (
                !is_string($actualBackupSha)
                || !hash_equals(
                    $backupSha,
                    strtolower(
                        $actualBackupSha
                    )
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Recovery checksum mismatch: {$relativePath}",
                    ]
                );
            }


            /*
             * Detect source changes that occurred AFTER backup.
             */
            $currentSha =
                hash_file(
                    'sha256',
                    $targetPath
                );

            if (
                !is_string($currentSha)
                || !hash_equals(
                    $backupSha,
                    strtolower($currentSha)
                )
            ) {
                return DeploymentFileMutationResult::invalid(
                    [
                        "Application source changed after backup: {$relativePath}",
                    ]
                );
            }


            /*
            |--------------------------------------------------------------------------
            | REPLACE ALSO REQUIRES VALID PAYLOAD
            |--------------------------------------------------------------------------
            */

            $payloadPath = null;

            if ($operation === 'replace') {

                $payloadPath =
                    $this->joinRelativePath(
                        $payloadRoot,
                        $relativePath
                    );

                $payloadError =
                    $this->verifyPayload(
                        $payloadPath,
                        $expectedSha,
                        $relativePath
                    );

                if ($payloadError !== null) {
                    return DeploymentFileMutationResult::invalid(
                        [$payloadError]
                    );
                }
            }

            $operations[] = [
                'operation' =>
                    $operation,

                'relative_path' =>
                    $relativePath,

                'target_path' =>
                    $targetPath,

                'payload_path' =>
                    $payloadPath,

                'sha256' =>
                    $expectedSha,

                'backup_sha256' =>
                    $backupSha,

                'backup_file' =>
                    $backupFile,
            ];
        }


        /*
         * A replace/delete package cannot proceed with an invalid
         * backup result, even if malformed metadata somehow resulted
         * in an empty operation collection.
         */
        if (
            $needsBackup
            && !$backup->isValid()
        ) {
            return DeploymentFileMutationResult::invalid(
                [
                    'Valid recovery backup is required before mutation.',
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MUTATION
        |--------------------------------------------------------------------------
        */

        $applied = [];

        try {

            foreach (
                $operations
                as $operation
            ) {

                $type =
                    $operation['operation'];

                $relativePath =
                    $operation['relative_path'];

                $target =
                    $operation['target_path'];


                /*
                |--------------------------------------------------------------------------
                | ADD
                |--------------------------------------------------------------------------
                */

                if ($type === 'add') {

                    $applied[] =
                        $this->executor->apply(
                            $operation
                        );

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | REPLACE
                |--------------------------------------------------------------------------
                */

                if ($type === 'replace') {

                    $applied[] =
                        $this->executor->apply(
                            $operation
                        );

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | DELETE
                |--------------------------------------------------------------------------
                */

                if ($type === 'delete') {

                    $applied[] =
                        $this->executor->apply(
                            $operation
                        );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | POST-MUTATION VERIFICATION
            |--------------------------------------------------------------------------
            */

            foreach (
                $operations
                as $operation
            ) {

                $type =
                    $operation['operation'];

                $target =
                    $operation['target_path'];

                $relativePath =
                    $operation['relative_path'];

                if ($type === 'delete') {

                    if (
                        file_exists($target)
                        || is_link($target)
                    ) {
                        throw new RuntimeException(
                            'Deleted target still exists: '
                            .$relativePath
                        );
                    }

                    continue;
                }

                if (!is_file($target)) {
                    throw new RuntimeException(
                        'Mutated target is missing: '
                        .$relativePath
                    );
                }

                $actual =
                    hash_file(
                        'sha256',
                        $target
                    );

                if (
                    !is_string($actual)
                    || !hash_equals(
                        $operation['sha256'],
                        strtolower($actual)
                    )
                ) {
                    throw new RuntimeException(
                        'Post-mutation checksum mismatch: '
                        .$relativePath
                    );
                }
            }

            return DeploymentFileMutationResult::valid(
                $applied
            );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | BEST-EFFORT COMPENSATION
            |--------------------------------------------------------------------------
            |
            | This is NOT yet our formal rollback subsystem.
            | It only attempts to restore mutations from the current call.
            |--------------------------------------------------------------------------
            */

            $this->compensate(
                $applicationRoot,
                $backup,
                $applied
            );

            return DeploymentFileMutationResult::invalid(
                [
                    'File mutation failed: '
                    .$e->getMessage(),
                ],
                []
            );
        }
    }

    public function compensateCompletedMutation(
        string $applicationRoot,
        DeploymentBackupResult $backup,
        DeploymentFileMutationResult $mutation
    ): DeploymentFileMutationResult {
        $applicationRoot = $this->normalizeRoot($applicationRoot);
        $errors = [];

        foreach (array_reverse($mutation->files()) as $file) {
            $operation = strtolower((string) ($file['operation'] ?? ''));
            $relativePath = $this->normalizeRelativePath(
                (string) ($file['relative_path'] ?? '')
            );
            $deployedSha = strtolower((string) ($file['sha256'] ?? ''));

            if (!$this->isSafeRelativePath($relativePath)) {
                $errors[] = 'Unsafe compensation path: ' . $relativePath;
                continue;
            }

            $target = $this->joinRelativePath($applicationRoot, $relativePath);

            try {
                if ($operation === 'add') {
                    $actualSha = is_file($target)
                        ? hash_file('sha256', $target)
                        : false;

                    if (
                        !is_string($actualSha)
                        || !preg_match('/^[a-f0-9]{64}$/', $deployedSha)
                        || !hash_equals($deployedSha, strtolower($actualSha))
                    ) {
                        throw new RuntimeException(
                            'Added file changed before compensation: ' . $relativePath
                        );
                    }

                    if (!unlink($target)) {
                        throw new RuntimeException(
                            'Unable to remove added file: ' . $relativePath
                        );
                    }

                    continue;
                }

                if ($operation !== 'replace' && $operation !== 'delete') {
                    throw new RuntimeException(
                        'Unknown compensated operation: ' . $relativePath
                    );
                }

                if ($operation === 'replace') {
                    $actualSha = is_file($target)
                        ? hash_file('sha256', $target)
                        : false;

                    if (
                        !is_string($actualSha)
                        || !preg_match('/^[a-f0-9]{64}$/', $deployedSha)
                        || !hash_equals($deployedSha, strtolower($actualSha))
                    ) {
                        throw new RuntimeException(
                            'Replaced file changed before compensation: ' . $relativePath
                        );
                    }
                } elseif (file_exists($target) || is_link($target)) {
                    throw new RuntimeException(
                        'Deleted target reappeared before compensation: ' . $relativePath
                    );
                }

                $backupFile = $this->joinRelativePath(
                    rtrim($backup->recoveryPath(), DIRECTORY_SEPARATOR)
                        . DIRECTORY_SEPARATOR . 'files',
                    $relativePath
                );
                $backupSha = is_file($backupFile)
                    ? hash_file('sha256', $backupFile)
                    : false;

                if (!is_string($backupSha)) {
                    throw new RuntimeException(
                        'Recovery file is unavailable: ' . $relativePath
                    );
                }

                $this->executor->apply([
                    'operation' => $operation === 'replace' ? 'replace' : 'add',
                    'relative_path' => $relativePath,
                    'target_path' => $target,
                    'payload_path' => $backupFile,
                    'sha256' => strtolower($backupSha),
                ]);
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors !== []) {
            return DeploymentFileMutationResult::invalid($errors);
        }

        return DeploymentFileMutationResult::valid([]);
    }


    private function verifyPayload(
        string $payloadPath,
        string $expectedSha,
        string $relativePath
    ): ?string {

        if (!is_file($payloadPath)) {
            return
                'Payload file is missing: '
                .$relativePath;
        }

        if (
            !preg_match(
                '/^[a-f0-9]{64}$/',
                $expectedSha
            )
        ) {
            return
                'Payload checksum metadata is invalid: '
                .$relativePath;
        }

        $actual =
            hash_file(
                'sha256',
                $payloadPath
            );

        if (
            !is_string($actual)
            || !hash_equals(
                $expectedSha,
                strtolower($actual)
            )
        ) {
            return
                'Payload checksum mismatch: '
                .$relativePath;
        }

        return null;
    }


    private function writePayloadSafely(
        string $source,
        string $target,
        string $expectedSha
    ): void {

        $temporary =
            dirname($target)
            .DIRECTORY_SEPARATOR
            .'.bgs-deploy-'
            .bin2hex(
                random_bytes(8)
            )
            .'.tmp';

        if (!copy($source, $temporary)) {
            throw new RuntimeException(
                'Unable to create temporary mutation file.'
            );
        }

        try {

            $temporarySha =
                hash_file(
                    'sha256',
                    $temporary
                );

            if (
                !is_string($temporarySha)
                || !hash_equals(
                    $expectedSha,
                    strtolower($temporarySha)
                )
            ) {
                throw new RuntimeException(
                    'Temporary mutation checksum verification failed.'
                );
            }

            /*
             * Windows cannot reliably rename over an existing file.
             * For replace, remove only after all preflight checks
             * and temporary payload verification have succeeded.
             */
            if (file_exists($target)) {
                if (!unlink($target)) {
                    throw new RuntimeException(
                        'Unable to remove existing mutation target.'
                    );
                }
            }

            if (!rename($temporary, $target)) {
                throw new RuntimeException(
                    'Unable to publish mutation target.'
                );
            }

        } finally {

            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }


    private function compensate(
        string $applicationRoot,
        DeploymentBackupResult $backup,
        array $applied
    ): void {

        foreach (
            array_reverse($applied)
            as $file
        ) {

            $operation =
                $file['operation'];

            $relativePath =
                $file['relative_path'];

            $target =
                $this->joinRelativePath(
                    $applicationRoot,
                    $relativePath
                );

            if ($operation === 'add') {

                if (is_file($target)) {
                    @unlink($target);
                }

                continue;
            }

            $backupFile =
                $this->joinRelativePath(
                    rtrim(
                        $backup->recoveryPath(),
                        DIRECTORY_SEPARATOR
                    )
                    .DIRECTORY_SEPARATOR
                    .'files',
                    $relativePath
                );

            if (!is_file($backupFile)) {
                continue;
            }

            $this->ensureParentDirectory(
                $target
            );

            @copy(
                $backupFile,
                $target
            );
        }
    }


    private function ensureParentDirectory(
        string $target
    ): void {

        $directory =
            dirname($target);

        if (
            !is_dir($directory)
            && !mkdir(
                $directory,
                0777,
                true
            )
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create mutation target directory.'
            );
        }
    }


    private function normalizeRoot(
        string $path
    ): string {
        return rtrim(
            str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $path
            ),
            DIRECTORY_SEPARATOR
        );
    }


    private function normalizeRelativePath(
        string $path
    ): string {
        return str_replace(
            '\\',
            '/',
            trim($path)
        );
    }


    private function isSafeRelativePath(
        string $path
    ): bool {

        if ($path === '') {
            return false;
        }

        if (
            str_starts_with(
                $path,
                '/'
            )
            || preg_match(
                '/^[A-Za-z]:\//',
                $path
            ) === 1
        ) {
            return false;
        }

        foreach (
            explode('/', $path)
            as $segment
        ) {

            if (
                $segment === ''
                || $segment === '.'
                || $segment === '..'
            ) {
                return false;
            }
        }

        return true;
    }


    private function joinRelativePath(
        string $root,
        string $relativePath
    ): string {

        return
            rtrim(
                $root,
                DIRECTORY_SEPARATOR
            )
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );
    }


    private function pathInsideRoot(
        string $path,
        string $root
    ): bool {

        $root =
            strtolower(
                rtrim(
                    str_replace(
                        '\\',
                        '/',
                        $root
                    ),
                    '/'
                )
            );

        $path =
            strtolower(
                str_replace(
                    '\\',
                    '/',
                    $path
                )
            );

        return
            $path !== $root
            && str_starts_with(
                $path,
                $root . '/'
            );
    }
}
