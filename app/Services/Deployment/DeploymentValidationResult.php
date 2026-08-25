<?php

namespace App\Services\Deployment;

final class DeploymentValidationResult
{
    private bool $valid;

    private array $errors;

    private ?DeploymentPackageManifest $manifest;

    private function __construct(
        bool $valid,
        array $errors,
        ?DeploymentPackageManifest $manifest
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->manifest = $manifest;
    }

    public static function valid(
        DeploymentPackageManifest $manifest
    ): self {
        return new self(
            true,
            [],
            $manifest
        );
    }

    public static function invalid(
        array $errors,
        ?DeploymentPackageManifest $manifest = null
    ): self {
        return new self(
            false,
            $errors,
            $manifest
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
}