<?php

namespace App\Services\Deployment;

use Illuminate\Http\UploadedFile;

interface DeploymentPackagePersistenceInterface
{
    /**
     * Persist an uploaded deployment package into private package storage.
     *
     * @return array{
     *     path: string,
     *     filename: string,
     *     sha256: string
     * }
     */
    public function persist(UploadedFile $package): array;
}