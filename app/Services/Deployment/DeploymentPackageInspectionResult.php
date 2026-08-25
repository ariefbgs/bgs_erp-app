<?php

namespace App\Services\Deployment;

final class DeploymentPackageInspectionResult
{
    private bool $valid;

    private array $errors;

    private string $packageRoot;

    private function __construct(
        bool $valid,
        array $errors,
        string $packageRoot
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->packageRoot = $packageRoot;
    }

    public static function valid(
        string $packageRoot
    ): self {
        return new self(
            true,
            [],
            $packageRoot
        );
    }

    public static function invalid(
        array $errors,
        string $packageRoot
    ): self {
        return new self(
            false,
            $errors,
            $packageRoot
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

    public function packageRoot(): string
    {
        return $this->packageRoot;
    }
}