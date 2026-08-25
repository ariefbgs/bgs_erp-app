<?php

namespace App\Services\Deployment;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DeploymentPackageInspector
{
    public function inspect(
        string $packageRoot,
        DeploymentPackageManifest $manifest
    ): DeploymentPackageInspectionResult {
        $errors = [];

        $packageRoot = rtrim(
            $packageRoot,
            DIRECTORY_SEPARATOR
        );

        $payloadRoot =
            $packageRoot
            .DIRECTORY_SEPARATOR
            .'payload';


        /*
        |--------------------------------------------------------------------------
        | PACKAGE ROOT
        |--------------------------------------------------------------------------
        */

        if (
            $packageRoot === ''
            || !is_dir($packageRoot)
        ) {
            return DeploymentPackageInspectionResult::invalid(
                ['Package root is missing or invalid.'],
                $packageRoot
            );
        }

        if (!is_dir($payloadRoot)) {
            return DeploymentPackageInspectionResult::invalid(
                ['Package payload directory is missing.'],
                $packageRoot
            );
        }


        /*
        |--------------------------------------------------------------------------
        | EXPECTED PHYSICAL PAYLOAD
        |--------------------------------------------------------------------------
        */

        $expectedPayloads = [];


        /*
        |--------------------------------------------------------------------------
        | FILE PAYLOAD
        |--------------------------------------------------------------------------
        */

        foreach ($manifest->files() as $index => $file) {

            if (!is_array($file)) {
                $errors[] =
                    "files.{$index} is invalid.";
                continue;
            }

            $operation = strtolower(
                trim(
                    (string) (
                        $file['operation']
                        ?? ''
                    )
                )
            );

            $relativePath = $this->normalizeRelativePath(
                (string) (
                    $file['relative_path']
                    ?? ''
                )
            );

            $sha256 = strtolower(
                trim(
                    (string) (
                        $file['sha256']
                        ?? ''
                    )
                )
            );


            /*
             * Delete means target file should disappear from
             * the application. It intentionally has no new payload.
             */
            if ($operation === 'delete') {
                continue;
            }


            if ($relativePath === '') {
                $errors[] =
                    "files.{$index} payload path is missing.";
                continue;
            }

            $payloadKey =
                strtolower($relativePath);

            $expectedPayloads[$payloadKey] = true;

            $physicalPath =
                $this->physicalPayloadPath(
                    $payloadRoot,
                    $relativePath
                );

            if (!is_file($physicalPath)) {
                $errors[] =
                    "files.{$index} payload is missing: {$relativePath}";
                continue;
            }

            $actualHash =
                hash_file(
                    'sha256',
                    $physicalPath
                );

            if (
                !is_string($actualHash)
                || !hash_equals(
                    $sha256,
                    strtolower($actualHash)
                )
            ) {
                $errors[] =
                    "files.{$index} checksum mismatch: {$relativePath}";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | MIGRATION PAYLOAD
        |--------------------------------------------------------------------------
        */

        foreach (
            $manifest->migrations()
            as $index => $migration
        ) {

            if (!is_array($migration)) {
                $errors[] =
                    "migrations.{$index} is invalid.";
                continue;
            }

            $relativePath = $this->normalizeRelativePath(
                (string) (
                    $migration['relative_path']
                    ?? ''
                )
            );

            $sha256 = strtolower(
                trim(
                    (string) (
                        $migration['sha256']
                        ?? ''
                    )
                )
            );

            if ($relativePath === '') {
                $errors[] =
                    "migrations.{$index} payload path is missing.";
                continue;
            }

            $payloadKey =
                strtolower($relativePath);

            $expectedPayloads[$payloadKey] = true;

            $physicalPath =
                $this->physicalPayloadPath(
                    $payloadRoot,
                    $relativePath
                );

            if (!is_file($physicalPath)) {
                $errors[] =
                    "migrations.{$index} payload is missing: {$relativePath}";
                continue;
            }

            $actualHash =
                hash_file(
                    'sha256',
                    $physicalPath
                );

            if (
                !is_string($actualHash)
                || !hash_equals(
                    $sha256,
                    strtolower($actualHash)
                )
            ) {
                $errors[] =
                    "migrations.{$index} checksum mismatch: {$relativePath}";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | REJECT UNLISTED PHYSICAL PAYLOAD
        |--------------------------------------------------------------------------
        */

        foreach (
            $this->listPayloadFiles($payloadRoot)
            as $physicalRelativePath
        ) {

            $key =
                strtolower(
                    $physicalRelativePath
                );

            if (!isset($expectedPayloads[$key])) {
                $errors[] =
                    "Unlisted payload file detected: {$physicalRelativePath}";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | RESULT
        |--------------------------------------------------------------------------
        */

        if ($errors !== []) {
            return DeploymentPackageInspectionResult::invalid(
                $errors,
                $packageRoot
            );
        }

        return DeploymentPackageInspectionResult::valid(
            $packageRoot
        );
    }


    private function normalizeRelativePath(
        string $path
    ): string {
        return ltrim(
            str_replace(
                '\\',
                '/',
                trim($path)
            ),
            '/'
        );
    }


    private function physicalPayloadPath(
        string $payloadRoot,
        string $relativePath
    ): string {
        return
            $payloadRoot
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $relativePath
            );
    }


    private function listPayloadFiles(
        string $payloadRoot
    ): array {
        $files = [];

        if (!is_dir($payloadRoot)) {
            return $files;
        }

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $payloadRoot,
                    FilesystemIterator::SKIP_DOTS
                )
            );

        foreach ($iterator as $item) {

            if (!$item->isFile()) {
                continue;
            }

            $absolutePath =
                $item->getPathname();

            $relativePath =
                substr(
                    $absolutePath,
                    strlen($payloadRoot) + 1
                );

            $relativePath =
                str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    $relativePath
                );

            $files[] = $relativePath;
        }

        sort(
            $files,
            SORT_STRING
        );

        return $files;
    }
}