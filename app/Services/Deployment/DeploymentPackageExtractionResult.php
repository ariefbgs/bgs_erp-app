<?php

namespace App\Services\Deployment;

final class DeploymentPackageExtractionResult
{
    private bool $valid;

    private array $errors;

    private string $stagingPath;

    private function __construct(
        bool $valid,
        array $errors,
        string $stagingPath
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->stagingPath = $stagingPath;
    }

    public static function valid(
        string $stagingPath
    ): self {
        return new self(
            true,
            [],
            $stagingPath
        );
    }

    public static function invalid(
        array $errors,
        string $stagingPath
    ): self {
        return new self(
            false,
            $errors,
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

    public function stagingPath(): string
    {
        return $this->stagingPath;
    }
}