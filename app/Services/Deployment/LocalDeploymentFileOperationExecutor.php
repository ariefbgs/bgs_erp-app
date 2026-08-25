<?php

namespace App\Services\Deployment;

use RuntimeException;

final class LocalDeploymentFileOperationExecutor
    implements DeploymentFileOperationExecutorInterface
{
    public function apply(array $operation): array
    {
        $type = $operation['operation'];
        $target = $operation['target_path'];
        $relativePath = $operation['relative_path'];

        if ($type === 'delete') {
            if (!is_file($target)) {
                throw new RuntimeException(
                    'Delete target is missing: '.$relativePath
                );
            }

            if (!unlink($target)) {
                throw new RuntimeException(
                    'Unable to delete application file: '.$relativePath
                );
            }

            return [
                'operation' => 'delete',
                'relative_path' => $relativePath,
                'sha256' => null,
            ];
        }

        $payload = $operation['payload_path'];
        $expectedSha = $operation['sha256'];

        $directory = dirname($target);

        if (
            !is_dir($directory)
            && !mkdir($directory, 0777, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                'Unable to create target directory.'
            );
        }

        $temporary =
            $directory
            .DIRECTORY_SEPARATOR
            .'.bgs-deploy-'
            .bin2hex(random_bytes(8))
            .'.tmp';

        if (!copy($payload, $temporary)) {
            throw new RuntimeException(
                'Unable to create temporary mutation file.'
            );
        }

        try {
            $sha = hash_file('sha256', $temporary);

            if (
                !is_string($sha)
                || !hash_equals(
                    $expectedSha,
                    strtolower($sha)
                )
            ) {
                throw new RuntimeException(
                    'Temporary mutation checksum mismatch.'
                );
            }

            if (file_exists($target)) {
                if (!unlink($target)) {
                    throw new RuntimeException(
                        'Unable to remove existing target.'
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

        return [
            'operation' => $type,
            'relative_path' => $relativePath,
            'sha256' => $expectedSha,
        ];
    }
}