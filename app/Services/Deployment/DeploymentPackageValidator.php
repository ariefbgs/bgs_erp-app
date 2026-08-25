<?php

namespace App\Services\Deployment;

final class DeploymentPackageValidator
{
    private const ALLOWED_SCOPES = [
        'application',
        'module',
        'feature',
        'hotfix',
    ];

    private const ALLOWED_FILE_OPERATIONS = [
        'add',
        'replace',
        'delete',
    ];

    /**
     * These paths are runtime, secret, dependency or deployment
     * working areas and may never be changed by a normal package.
     */
    private const BLOCKED_PATH_PREFIXES = [
        '.env',
        'storage',
        'vendor',
        'node_modules',
        '.git',
        'audit-output',
        'bootstrap/cache',
        'public/storage',
    ];

    public function validate(array $manifestData): DeploymentValidationResult
    {
        $errors = [];

        $releaseId = trim(
            (string) ($manifestData['release_id'] ?? '')
        );

        $version = trim(
            (string) ($manifestData['version'] ?? '')
        );

        $scope = trim(
            (string) ($manifestData['scope'] ?? '')
        );

        $module = trim(
            (string) ($manifestData['module'] ?? '')
        );

        $feature = trim(
            (string) ($manifestData['feature'] ?? '')
        );


        /*
        |--------------------------------------------------------------------------
        | CORE IDENTITY
        |--------------------------------------------------------------------------
        */

        if ($releaseId === '') {
            $errors[] = 'release_id is required.';
        }

        if ($version === '') {
            $errors[] = 'version is required.';
        }

        if ($scope === '') {
            $errors[] = 'scope is required.';
        } elseif (!in_array(
            $scope,
            self::ALLOWED_SCOPES,
            true
        )) {
            $errors[] = 'scope is invalid.';
        }


        /*
        |--------------------------------------------------------------------------
        | SCOPE CONTRACT
        |--------------------------------------------------------------------------
        */

        if (
            $scope === 'module'
            && $module === ''
        ) {
            $errors[] =
                'module is required for module scope.';
        }

        if ($scope === 'feature') {

            if ($module === '') {
                $errors[] =
                    'module is required for feature scope.';
            }

            if ($feature === '') {
                $errors[] =
                    'feature is required for feature scope.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | TOP-LEVEL COLLECTION TYPES
        |--------------------------------------------------------------------------
        */

        $files = $manifestData['files'] ?? [];

        $migrations =
            $manifestData['migrations'] ?? [];

        if (!is_array($files)) {
            $errors[] = 'files must be an array.';
            $files = [];
        }

        if (!is_array($migrations)) {
            $errors[] = 'migrations must be an array.';
            $migrations = [];
        }


        /*
        |--------------------------------------------------------------------------
        | FILE SECURITY
        |--------------------------------------------------------------------------
        */

        $seenFileTargets = [];

        foreach ($files as $index => $file) {

            if (!is_array($file)) {
                $errors[] =
                    "files.{$index} must be an object.";
                continue;
            }

            $operation = strtolower(
                trim(
                    (string) ($file['operation'] ?? '')
                )
            );

            $rawPath = trim(
                (string) (
                    $file['relative_path']
                    ?? ''
                )
            );

            $sha256 = trim(
                (string) ($file['sha256'] ?? '')
            );


            if (!in_array(
                $operation,
                self::ALLOWED_FILE_OPERATIONS,
                true
            )) {
                $errors[] =
                    "files.{$index}.operation is invalid.";
            }


            $pathError =
                $this->validateRelativePath(
                    $rawPath
                );

            if ($pathError !== null) {
                $errors[] =
                    "files.{$index}.path {$pathError}";
            }
            else {

                $normalizedPath =
                    $this->normalizePath($rawPath);

                if (
                    $this->isBlockedPath(
                        $normalizedPath
                    )
                ) {
                    $errors[] =
                        "files.{$index}.path is blocked.";
                }

                /*
                 * Case-insensitive comparison is intentional.
                 * Local development is Windows and a release
                 * must never contain two logically identical
                 * targets whose case differs.
                 */
                $targetKey = strtolower(
                    $normalizedPath
                );

                if (
                    isset(
                        $seenFileTargets[$targetKey]
                    )
                ) {
                    $errors[] =
                        "files.{$index} has duplicate path target.";
                }
                else {
                    $seenFileTargets[$targetKey] =
                        true;
                }
            }


            if (
                in_array(
                    $operation,
                    ['add', 'replace'],
                    true
                )
            ) {

                if ($sha256 === '') {
                    $errors[] =
                        "files.{$index}.sha256 is required.";
                }
                elseif (!$this->isValidSha256(
                    $sha256
                )) {
                    $errors[] =
                        "files.{$index}.sha256 is invalid.";
                }
            }
            elseif (
                $sha256 !== ''
                && !$this->isValidSha256(
                    $sha256
                )
            ) {
                $errors[] =
                    "files.{$index}.sha256 is invalid.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MIGRATION SECURITY
        |--------------------------------------------------------------------------
        */

        $seenMigrationNames = [];
        $seenMigrationPaths = [];

        foreach (
            $migrations
            as $index => $migration
        ) {

            if (!is_array($migration)) {
                $errors[] =
                    "migrations.{$index} must be an object.";
                continue;
            }

            $migrationName = trim(
                (string) (
                    $migration['migration_name']
                    ?? ''
                )
            );

            $rawPath = trim(
                (string) (
                    $migration['relative_path']
                    ?? ''
                )
            );

            $sha256 = trim(
                (string) (
                    $migration['sha256']
                    ?? ''
                )
            );


            if ($migrationName === '') {
                $errors[] =
                    "migrations.{$index}.migration_name is required.";
            }


            $pathError =
                $this->validateRelativePath(
                    $rawPath
                );

            if ($pathError !== null) {
                $errors[] =
                    "migrations.{$index}.migration path {$pathError}";
            }
            else {

                $normalizedPath =
                    $this->normalizePath(
                        $rawPath
                    );

                /*
                 * Migration package files are only allowed
                 * directly inside database/migrations.
                 */
                if (!preg_match(
                    '#^database/migrations/[^/]+\.php$#i',
                    $normalizedPath
                )) {
                    $errors[] =
                        "migrations.{$index}.migration path is invalid.";
                }

                $basename =
                    basename($normalizedPath);

                if (
                    $migrationName !== ''
                    && strcasecmp(
                        $basename,
                        $migrationName
                    ) !== 0
                ) {
                    $errors[] =
                        "migrations.{$index}.migration name does not match path.";
                }

                $pathKey =
                    strtolower(
                        $normalizedPath
                    );

                if (
                    isset(
                        $seenMigrationPaths[
                            $pathKey
                        ]
                    )
                ) {
                    $errors[] =
                        "migrations.{$index} has duplicate migration path.";
                }
                else {
                    $seenMigrationPaths[
                        $pathKey
                    ] = true;
                }
            }


            if (
                $migrationName !== ''
                && !preg_match(
                    '/\.php$/i',
                    $migrationName
                )
            ) {
                $errors[] =
                    "migrations.{$index}.migration name must be a php file.";
            }


            if ($migrationName !== '') {

                $nameKey =
                    strtolower(
                        $migrationName
                    );

                if (
                    isset(
                        $seenMigrationNames[
                            $nameKey
                        ]
                    )
                ) {
                    $errors[] =
                        "migrations.{$index} has duplicate migration identity.";
                }
                else {
                    $seenMigrationNames[
                        $nameKey
                    ] = true;
                }
            }


            if ($sha256 === '') {
                $errors[] =
                    "migrations.{$index}.sha256 is required.";
            }
            elseif (!$this->isValidSha256(
                $sha256
            )) {
                $errors[] =
                    "migrations.{$index}.sha256 is invalid.";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DOMAIN RESULT
        |--------------------------------------------------------------------------
        */

        $manifest =
            DeploymentPackageManifest::fromArray(
                $manifestData
            );

        if ($errors !== []) {
            return DeploymentValidationResult::invalid(
                $errors,
                $manifest
            );
        }

        return DeploymentValidationResult::valid(
            $manifest
        );
    }


    private function normalizePath(
        string $path
    ): string {
        $path = str_replace(
            '\\',
            '/',
            trim($path)
        );

        while (str_contains(
            $path,
            '//'
        )) {
            $path = str_replace(
                '//',
                '/',
                $path
            );
        }

        return $path;
    }


    private function validateRelativePath(
        string $path
    ): ?string {

        if ($path === '') {
            return 'is required.';
        }

        if (str_contains(
            $path,
            "\0"
        )) {
            return 'contains an invalid null byte.';
        }

        $normalized =
            str_replace(
                '\\',
                '/',
                trim($path)
            );


        /*
         * Linux / UNC absolute path.
         */
        if (
            str_starts_with(
                $normalized,
                '/'
            )
        ) {
            return 'must be relative.';
        }


        /*
         * Windows drive absolute path.
         */
        if (preg_match(
            '/^[A-Za-z]:\//',
            $normalized
        )) {
            return 'must be relative.';
        }


        /*
         * Any .. path segment is forbidden.
         * We do not canonicalize it away because
         * a release manifest must be explicit.
         */
        $segments =
            explode(
                '/',
                $normalized
            );

        foreach ($segments as $segment) {
            if ($segment === '..') {
                return 'contains parent directory traversal.';
            }
        }

        return null;
    }


    private function isBlockedPath(
        string $path
    ): bool {

        $path =
            strtolower(
                trim(
                    $path,
                    '/'
                )
            );

        /*
         * Protect .env and all environment variants.
         */
        if (
            $path === '.env'
            || str_starts_with(
                $path,
                '.env.'
            )
        ) {
            return true;
        }

        foreach (
            self::BLOCKED_PATH_PREFIXES
            as $blocked
        ) {

            $blocked =
                strtolower(
                    trim(
                        $blocked,
                        '/'
                    )
                );

            if (
                $path === $blocked
                || str_starts_with(
                    $path,
                    $blocked . '/'
                )
            ) {
                return true;
            }
        }

        return false;
    }


    private function isValidSha256(
        string $sha256
    ): bool {
        return preg_match(
            '/^[A-Fa-f0-9]{64}$/',
            $sha256
        ) === 1;
    }
}