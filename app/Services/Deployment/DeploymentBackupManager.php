<?php

namespace App\Services\Deployment;

use Throwable;

final class DeploymentBackupManager
{
    public function backup(
        string $applicationRoot,
        string $recoveryPath,
        DeploymentPackageManifest $manifest
    ): DeploymentBackupResult {

        $applicationRoot =
            $this->normalizeRoot($applicationRoot);

        $recoveryPath =
            $this->normalizeRoot($recoveryPath);


        /*
        |--------------------------------------------------------------------------
        | 1. APPLICATION ROOT MUST EXIST
        |--------------------------------------------------------------------------
        */

        if (!is_dir($applicationRoot)) {
            return DeploymentBackupResult::invalid(
                ['Application root does not exist.'],
                $recoveryPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 2. NEVER OVERWRITE A NON-EMPTY RECOVERY SET
        |--------------------------------------------------------------------------
        */

        if (
            is_dir($recoveryPath)
            && !$this->directoryIsEmpty($recoveryPath)
        ) {
            return DeploymentBackupResult::invalid(
                [
                    'Recovery directory already exists and is not empty.',
                ],
                $recoveryPath
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 3. DETERMINE FILES THAT REQUIRE BACKUP
        |--------------------------------------------------------------------------
        |
        | ADD:
        |   A new file has no previous application state to preserve.
        |
        | REPLACE / DELETE:
        |   Existing application file MUST be preserved before mutation.
        |--------------------------------------------------------------------------
        */

        $backupCandidates = [];

        foreach ($manifest->files() as $file) {
            $operation =
                strtolower(
                    (string) ($file['operation'] ?? '')
                );

            $relativePath =
                (string) ($file['relative_path'] ?? '');

            if (
                $operation !== 'replace'
                && $operation !== 'delete'
            ) {
                continue;
            }

            if (!$this->isSafeRelativePath($relativePath)) {
                return DeploymentBackupResult::invalid(
                    [
                        'Unsafe backup relative path: '
                        .$relativePath,
                    ],
                    $recoveryPath
                );
            }

            $sourcePath =
                $this->joinRelativePath(
                    $applicationRoot,
                    $relativePath
                );

            if (!is_file($sourcePath)) {
                return DeploymentBackupResult::invalid(
                    [
                        'Source file is missing for '
                        .$operation
                        .' operation: '
                        .$relativePath,
                    ],
                    $recoveryPath
                );
            }

            $backupCandidates[] = [
                'operation' => $operation,
                'relative_path' => $relativePath,
                'source_path' => $sourcePath,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | 4. ADD-ONLY PACKAGE MAY REQUIRE NO PHYSICAL BACKUP
        |--------------------------------------------------------------------------
        |
        | We still return a valid recovery result. No recovery directory needs
        | to be created when there is nothing from the existing application
        | that needs preservation.
        |--------------------------------------------------------------------------
        */

        if ($backupCandidates === []) {
            return DeploymentBackupResult::valid(
                $recoveryPath,
                []
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 5. CREATE RECOVERY SET
        |--------------------------------------------------------------------------
        */

        $filesRoot =
            $recoveryPath
            .DIRECTORY_SEPARATOR
            .'files';

        try {
            if (
                !is_dir($filesRoot)
                && !mkdir(
                    $filesRoot,
                    0777,
                    true
                )
                && !is_dir($filesRoot)
            ) {
                throw new \RuntimeException(
                    'Unable to create recovery files directory.'
                );
            }

            $backedUpFiles = [];

            foreach ($backupCandidates as $candidate) {
                $relativePath =
                    $candidate['relative_path'];

                $sourcePath =
                    $candidate['source_path'];

                $targetPath =
                    $this->joinRelativePath(
                        $filesRoot,
                        $relativePath
                    );

                if (
                    !$this->pathIsInsideRoot(
                        $targetPath,
                        $filesRoot
                    )
                ) {
                    throw new \RuntimeException(
                        'Backup target escaped recovery root: '
                        .$relativePath
                    );
                }

                $targetDirectory =
                    dirname($targetPath);

                if (
                    !is_dir($targetDirectory)
                    && !mkdir(
                        $targetDirectory,
                        0777,
                        true
                    )
                    && !is_dir($targetDirectory)
                ) {
                    throw new \RuntimeException(
                        'Unable to create recovery directory for: '
                        .$relativePath
                    );
                }

                if (!copy($sourcePath, $targetPath)) {
                    throw new \RuntimeException(
                        'Unable to backup source file: '
                        .$relativePath
                    );
                }

                $sourceChecksum =
                    hash_file(
                        'sha256',
                        $sourcePath
                    );

                $backupChecksum =
                    hash_file(
                        'sha256',
                        $targetPath
                    );

                if (
                    !is_string($sourceChecksum)
                    || !is_string($backupChecksum)
                    || !hash_equals(
                        $sourceChecksum,
                        $backupChecksum
                    )
                ) {
                    throw new \RuntimeException(
                        'Backup checksum verification failed: '
                        .$relativePath
                    );
                }

                $backedUpFiles[] = [
                    'operation' =>
                        $candidate['operation'],

                    'relative_path' =>
                        $relativePath,

                    'sha256' =>
                        $backupChecksum,
                ];
            }

            return DeploymentBackupResult::valid(
                $recoveryPath,
                $backedUpFiles
            );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | FAIL CLOSED
            |--------------------------------------------------------------------------
            |
            | A partially-created recovery set must never be accepted.
            |--------------------------------------------------------------------------
            */

            $this->removeDirectory(
                $recoveryPath
            );

            return DeploymentBackupResult::invalid(
                [
                    'Backup failed: '
                    .$e->getMessage(),
                ],
                $recoveryPath
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


    private function isSafeRelativePath(
        string $relativePath
    ): bool {
        if ($relativePath === '') {
            return false;
        }

        $path =
            str_replace(
                '\\',
                '/',
                $relativePath
            );

        if (
            str_starts_with($path, '/')
            || preg_match(
                '/^[A-Za-z]:\//',
                $path
            ) === 1
        ) {
            return false;
        }

        $segments =
            explode('/', $path);

        foreach ($segments as $segment) {
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
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $relativePath
            );
    }


    private function pathIsInsideRoot(
        string $path,
        string $root
    ): bool {
        $normalizedRoot =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $root
                ),
                '/'
            )
            .'/';

        $normalizedPath =
            str_replace(
                '\\',
                '/',
                $path
            );

        return str_starts_with(
            $normalizedPath,
            $normalizedRoot
        );
    }


    private function directoryIsEmpty(
        string $path
    ): bool {
        $items =
            scandir($path);

        if ($items === false) {
            return false;
        }

        return count(
            array_diff(
                $items,
                ['.', '..']
            )
        ) === 0;
    }


    private function removeDirectory(
        string $path
    ): void {
        if (!is_dir($path)) {
            return;
        }

        $items =
            scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $target =
                $path
                .DIRECTORY_SEPARATOR
                .$item;

            if (is_dir($target)) {
                $this->removeDirectory(
                    $target
                );
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}