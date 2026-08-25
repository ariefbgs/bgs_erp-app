<?php

namespace App\Services\Deployment;

final class DeploymentMigrationResult
{
    private bool $valid;

    private array $errors;

    private array $applied;

    private array $skipped;

    private ?string $failed;

    public function __construct(
        bool $valid,
        array $errors = [],
        array $applied = [],
        array $skipped = [],
        ?string $failed = null
    ) {
        $this->valid = $valid;
        $this->errors = array_values($errors);
        $this->applied = array_values($applied);
        $this->skipped = array_values($skipped);
        $this->failed = $failed;
    }

    public static function success(
        array $applied = [],
        array $skipped = []
    ): self {
        return new self(
            true,
            [],
            $applied,
            $skipped,
            null
        );
    }

    public static function failure(
        array $errors,
        array $applied = [],
        array $skipped = [],
        ?string $failed = null
    ): self {
        return new self(
            false,
            $errors,
            $applied,
            $skipped,
            $failed
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

    public function applied(): array
    {
        return $this->applied;
    }

    public function skipped(): array
    {
        return $this->skipped;
    }

    public function failed(): ?string
    {
        return $this->failed;
    }
}