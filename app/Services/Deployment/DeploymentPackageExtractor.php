<?php

namespace App\Services\Deployment;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class DeploymentPackageExtractor
{
    private const MAX_ENTRIES = 1000;

    private const MAX_UNCOMPRESSED_BYTES = 100 * 1024 * 1024;

    public function extract(
        string $zipPath,
        string $stagingPath
    ): DeploymentPackageExtractionResult {
        $errors = [];

        if (
            $zipPath === ''
            || !is_file($zipPath)
        ) {
            return DeploymentPackageExtractionResult::invalid(
                ['ZIP package is missing or invalid.'],
                $stagingPath
            );
        }

        if ($this->isNonEmptyDirectory($stagingPath)) {
            return DeploymentPackageExtractionResult::invalid(
                ['Staging directory must be empty.'],
                $stagingPath
            );
        }

        $zip = new ZipArchive();

        $openResult = $zip->open($zipPath);

        if ($openResult !== true) {
            return DeploymentPackageExtractionResult::invalid(
                ['ZIP package is invalid.'],
                $stagingPath
            );
        }

        try {
            if ($zip->numFiles > self::MAX_ENTRIES) {
                $errors[] = 'ZIP entry count exceeds allowed limit.';
            }

            $entries = [];
            $seen = [];
            $totalUncompressed = 0;
            $hasManifest = false;

            /*
            |--------------------------------------------------------------------------
            | PRE-FLIGHT ALL ZIP ENTRIES
            |--------------------------------------------------------------------------
            */

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if (!is_array($stat)) {
                    $errors[] =
                        "Unable to inspect ZIP entry at index {$i}.";
                    continue;
                }

                $rawName =
                    (string) ($stat['name'] ?? '');

                $normalized =
                    $this->normalizeEntryName($rawName);

                if ($normalized === '') {
                    $errors[] =
                        "ZIP entry {$i} has invalid name.";
                    continue;
                }

                if ($this->isUnsafeEntryPath($rawName, $normalized)) {
                    $errors[] =
                        "ZIP entry path is unsafe: {$rawName}";
                    continue;
                }

                $key = strtolower($normalized);

                if (isset($seen[$key])) {
                    $errors[] =
                        "Duplicate ZIP entry detected: {$normalized}";
                    continue;
                }

                $seen[$key] = true;

                if ($normalized === 'manifest.json') {
                    $hasManifest = true;
                } elseif (
                    !str_starts_with(
                        $normalized,
                        'payload/'
                    )
                ) {
                    /*
                     * Directory entry "payload/" itself is okay.
                     */
                    if ($normalized !== 'payload') {
                        $errors[] =
                            "Unexpected ZIP entry: {$normalized}";
                    }
                }

                $size =
                    (int) ($stat['size'] ?? 0);

                if ($size < 0) {
                    $errors[] =
                        "ZIP entry size is invalid: {$normalized}";
                    continue;
                }

                $totalUncompressed += $size;

                if (
                    $totalUncompressed
                    > self::MAX_UNCOMPRESSED_BYTES
                ) {
                    $errors[] =
                        'ZIP uncompressed size exceeds allowed limit.';
                }

                if ($this->isUnsafeSpecialEntry($zip, $i)) {
                    $errors[] =
                        "ZIP special/symlink entry is not allowed: {$normalized}";
                }

                $entries[] = [
                    'index' => $i,
                    'name' => $normalized,
                    'raw_name' => $rawName,
                ];
            }

            if (!$hasManifest) {
                $errors[] =
                    'manifest.json is required at ZIP root.';
            }

            if ($errors !== []) {
                return DeploymentPackageExtractionResult::invalid(
                    array_values(array_unique($errors)),
                    $stagingPath
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CREATE CLEAN STAGING
            |--------------------------------------------------------------------------
            */

            if (!is_dir($stagingPath)) {
                if (!mkdir(
                    $stagingPath,
                    0777,
                    true
                ) && !is_dir($stagingPath)) {
                    return DeploymentPackageExtractionResult::invalid(
                        ['Unable to create staging directory.'],
                        $stagingPath
                    );
                }
            }

            $stagingReal =
                realpath($stagingPath);

            if ($stagingReal === false) {
                return DeploymentPackageExtractionResult::invalid(
                    ['Unable to resolve staging directory.'],
                    $stagingPath
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CONTROLLED EXTRACTION ENTRY-BY-ENTRY
            |--------------------------------------------------------------------------
            */

            foreach ($entries as $entry) {
                $normalized = $entry['name'];

                /*
                 * Directory entries.
                 */
                if (
                    str_ends_with(
                        $entry['raw_name'],
                        '/'
                    )
                    || str_ends_with(
                        $entry['raw_name'],
                        '\\'
                    )
                ) {
                    $directory =
                        $this->targetPath(
                            $stagingPath,
                            $normalized
                        );

                    if (!$this->isTargetInsideStaging(
                        $directory,
                        $stagingReal
                    )) {
                        $errors[] =
                            "Extraction target escaped staging: {$normalized}";
                        break;
                    }

                    if (
                        !is_dir($directory)
                        && !mkdir(
                            $directory,
                            0777,
                            true
                        )
                        && !is_dir($directory)
                    ) {
                        $errors[] =
                            "Unable to create extraction directory: {$normalized}";
                        break;
                    }

                    continue;
                }

                $target =
                    $this->targetPath(
                        $stagingPath,
                        $normalized
                    );

                if (!$this->isTargetInsideStaging(
                    $target,
                    $stagingReal
                )) {
                    $errors[] =
                        "Extraction target escaped staging: {$normalized}";
                    break;
                }

                $parent = dirname($target);

                if (
                    !is_dir($parent)
                    && !mkdir(
                        $parent,
                        0777,
                        true
                    )
                    && !is_dir($parent)
                ) {
                    $errors[] =
                        "Unable to create extraction directory: {$normalized}";
                    break;
                }

                $stream =
                    $zip->getStream(
                        $entry['raw_name']
                    );

                if (!is_resource($stream)) {
                    $errors[] =
                        "Unable to read ZIP entry: {$normalized}";
                    break;
                }

                $output =
                    fopen(
                        $target,
                        'wb'
                    );

                if (!is_resource($output)) {
                    fclose($stream);

                    $errors[] =
                        "Unable to create staging file: {$normalized}";
                    break;
                }

                $copied =
                    stream_copy_to_stream(
                        $stream,
                        $output
                    );

                fclose($stream);
                fclose($output);

                if ($copied === false) {
                    $errors[] =
                        "Unable to extract ZIP entry: {$normalized}";
                    break;
                }
            }

            if ($errors !== []) {
                $this->removeDirectory($stagingPath);

                return DeploymentPackageExtractionResult::invalid(
                    array_values(array_unique($errors)),
                    $stagingPath
                );
            }

            return DeploymentPackageExtractionResult::valid(
                $stagingPath
            );
        } finally {
            $zip->close();
        }
    }


    private function normalizeEntryName(
        string $name
    ): string {
        $normalized =
            str_replace(
                '\\',
                '/',
                trim($name)
            );

        while (str_contains(
            $normalized,
            '//'
        )) {
            $normalized =
                str_replace(
                    '//',
                    '/',
                    $normalized
                );
        }

        return rtrim(
            $normalized,
            '/'
        );
    }


    private function isUnsafeEntryPath(
        string $rawName,
        string $normalized
    ): bool {
        if (
            $rawName === ''
            || str_contains(
                $rawName,
                "\0"
            )
        ) {
            return true;
        }

        if (
            str_starts_with(
                $normalized,
                '/'
            )
        ) {
            return true;
        }

        if (preg_match(
            '/^[A-Za-z]:\//',
            $normalized
        )) {
            return true;
        }

        foreach (
            explode('/', $normalized)
            as $segment
        ) {
            if ($segment === '..') {
                return true;
            }
        }

        return false;
    }


    private function isUnsafeSpecialEntry(
        ZipArchive $zip,
        int $index
    ): bool {
        if (!method_exists(
            $zip,
            'getExternalAttributesIndex'
        )) {
            return false;
        }

        $opsys = 0;
        $attr = 0;

        $ok =
            $zip->getExternalAttributesIndex(
                $index,
                $opsys,
                $attr
            );

        if (!$ok) {
            return false;
        }

        /*
         * UNIX mode stored in upper 16 bits.
         * 0120000 = symlink.
         */
        $mode =
            ($attr >> 16)
            & 0xF000;

        return $mode === 0xA000;
    }


    private function targetPath(
        string $stagingPath,
        string $normalized
    ): string {
        return
            rtrim(
                $stagingPath,
                DIRECTORY_SEPARATOR
            )
            .DIRECTORY_SEPARATOR
            .str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                $normalized
            );
    }


    private function isTargetInsideStaging(
        string $target,
        string $stagingReal
    ): bool {
        $parent = dirname($target);

        while (!is_dir($parent)) {
            $next = dirname($parent);

            if ($next === $parent) {
                break;
            }

            $parent = $next;
        }

        $parentReal =
            realpath($parent);

        if ($parentReal === false) {
            return false;
        }

        $parentReal =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $parentReal
                ),
                '/'
            );

        $stagingReal =
            rtrim(
                str_replace(
                    '\\',
                    '/',
                    $stagingReal
                ),
                '/'
            );

        return
            $parentReal === $stagingReal
            || str_starts_with(
                $parentReal,
                $stagingReal . '/'
            );
    }


    private function isNonEmptyDirectory(
        string $path
    ): bool {
        if (!is_dir($path)) {
            return false;
        }

        $iterator =
            new FilesystemIterator(
                $path,
                FilesystemIterator::SKIP_DOTS
            );

        return $iterator->valid();
    }


    private function removeDirectory(
        string $path
    ): void {
        if (!is_dir($path)) {
            return;
        }

        $iterator =
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir(
                    $item->getPathname()
                );
            } else {
                @unlink(
                    $item->getPathname()
                );
            }
        }

        @rmdir($path);
    }
}