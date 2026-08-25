<?php

namespace App\Services\Deployment;

final class DeploymentOrchestrationResult
{
    private bool $successful;

    /**
     * @var array<int, string>
     */
    private array $errors;

    private string $releaseStatus;

    /**
     * @param array<int, string> $errors
     */
    private function __construct(
        bool $successful,
        array $errors,
        string $releaseStatus
    ) {
        $this->successful = $successful;
        $this->errors = array_values($errors);
        $this->releaseStatus = $releaseStatus;
    }

    public static function success(string $releaseStatus): self
    {
        return new self(
            true,
            [],
            $releaseStatus
        );
    }

    /**
     * @param array<int, string> $errors
     */
    public static function failure(
        array $errors,
        string $releaseStatus
    ): self {
        return new self(
            false,
            $errors,
            $releaseStatus
        );
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * @return array<int, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function releaseStatus(): string
    {
        return $this->releaseStatus;
    }
}