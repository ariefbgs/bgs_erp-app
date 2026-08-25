<?php

namespace App\Services\Deployment;

final class DeploymentFileMutationResult
{
    private bool $valid;

    private array $errors;

    private array $files;

    private function __construct(
        bool $valid,
        array $errors,
        array $files
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->files = array_values($files);
    }

    public static function valid(
        array $files
    ): self {
        return new self(
            true,
            [],
            $files
        );
    }

    public static function invalid(
        array $errors,
        array $files = []
    ): self {
        return new self(
            false,
            $errors,
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

    public function files(): array
    {
        return $this->files;
    }
}