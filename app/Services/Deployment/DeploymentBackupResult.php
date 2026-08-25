<?php

namespace App\Services\Deployment;

final class DeploymentBackupResult
{
    private bool $valid;

    private array $errors;

    private string $recoveryPath;

    private array $files;

    private function __construct(
        bool $valid,
        array $errors,
        string $recoveryPath,
        array $files
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->recoveryPath = $recoveryPath;
        $this->files = array_values($files);
    }

    public static function valid(
        string $recoveryPath,
        array $files
    ): self {
        return new self(
            true,
            [],
            $recoveryPath,
            $files
        );
    }

    public static function invalid(
        array $errors,
        string $recoveryPath,
        array $files = []
    ): self {
        return new self(
            false,
            $errors,
            $recoveryPath,
            $files
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

    public function recoveryPath(): string
    {
        return $this->recoveryPath;
    }

    public function files(): array
    {
        return $this->files;
    }
}