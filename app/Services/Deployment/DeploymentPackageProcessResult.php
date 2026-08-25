<?php

namespace App\Services\Deployment;

final class DeploymentPackageProcessResult
{
    private bool $valid;

    private array $errors;

    private ?DeploymentPackageManifest $manifest;

    private string $stagingPath;

    private function __construct(
        bool $valid,
        array $errors,
        ?DeploymentPackageManifest $manifest,
        string $stagingPath
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->manifest = $manifest;
        $this->stagingPath = $stagingPath;
    }

    public static function valid(
        DeploymentPackageManifest $manifest,
        string $stagingPath
    ): self {
        return new self(
            true,
            [],
            $manifest,
            $stagingPath
        );
    }

    public static function invalid(
        array $errors,
        string $stagingPath,
        ?DeploymentPackageManifest $manifest = null
    ): self {
        return new self(
            false,
            $errors,
            $manifest,
            $stagingPath
        );
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function manifest(): ?DeploymentPackageManifest
    {
        return $this->manifest;
    }

    public function stagingPath(): string
    {
        return $this->stagingPath;
    }
}