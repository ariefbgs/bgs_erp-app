<?php

namespace App\Services\Deployment;

final class DeploymentPackageRegistrationResult
{
    public function __construct(
        private bool $success,
        private ?int $releaseId = null,
        private ?string $message = null
    ) {
    }

    public static function success(int $releaseId): self
    {
        return new self(
            success: true,
            releaseId: $releaseId
        );
    }

    public static function failure(string $message): self
    {
        return new self(
            success: false,
            releaseId: null,
            message: $message
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function releaseId(): ?int
    {
        return $this->releaseId;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}