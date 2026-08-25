<?php

namespace App\Services\Deployment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class LocalDeploymentPackagePersistence implements DeploymentPackagePersistenceInterface
{
    public function persist(UploadedFile $package): array
    {
        $packageRoot = (string) config(
            'deployment.paths.packages'
        );

        if ($packageRoot === '') {
            throw new RuntimeException(
                'Deployment package storage path is not configured.'
            );
        }

        if (
            !is_dir($packageRoot)
            && !mkdir($packageRoot, 0750, true)
            && !is_dir($packageRoot)
        ) {
            throw new RuntimeException(
                'Unable to create deployment package storage directory.'
            );
        }

        $filename =
            Str::uuid()->toString().'.zip';

        $package->move(
            $packageRoot,
            $filename
        );

        $path =
            $packageRoot
            .DIRECTORY_SEPARATOR
            .$filename;

        if (!is_file($path)) {
            throw new RuntimeException(
                'Deployment package persistence failed.'
            );
        }

        $sha256 =
            hash_file(
                'sha256',
                $path
            );

        if (!is_string($sha256)) {
            @unlink($path);

            throw new RuntimeException(
                'Unable to calculate deployment package checksum.'
            );
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'sha256' => strtolower($sha256),
        ];
    }
}