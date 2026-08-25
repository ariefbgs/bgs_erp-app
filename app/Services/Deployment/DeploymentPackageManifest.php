<?php

namespace App\Services\Deployment;

final class DeploymentPackageManifest
{
    private string $releaseId;

    private string $version;

    private string $scope;

    private ?string $module;

    private ?string $feature;

    private array $files;

    private array $migrations;

    public function __construct(
        string $releaseId,
        string $version,
        string $scope,
        ?string $module = null,
        ?string $feature = null,
        array $files = [],
        array $migrations = []
    ) {
        $this->releaseId = trim($releaseId);
        $this->version = trim($version);
        $this->scope = trim($scope);

        $module = $module !== null
            ? trim($module)
            : null;

        $feature = $feature !== null
            ? trim($feature)
            : null;

        $this->module =
            $module === ''
                ? null
                : $module;

        $this->feature =
            $feature === ''
                ? null
                : $feature;

        $this->files = array_values($files);
        $this->migrations = array_values($migrations);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['release_id'] ?? ''),
            (string) ($data['version'] ?? ''),
            (string) ($data['scope'] ?? ''),
            array_key_exists('module', $data)
                ? (string) $data['module']
                : null,
            array_key_exists('feature', $data)
                ? (string) $data['feature']
                : null,
            is_array($data['files'] ?? null)
                ? $data['files']
                : [],
            is_array($data['migrations'] ?? null)
                ? $data['migrations']
                : []
        );
    }

    public function releaseId(): string
    {
        return $this->releaseId;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function scope(): string
    {
        return $this->scope;
    }

    public function module(): ?string
    {
        return $this->module;
    }

    public function feature(): ?string
    {
        return $this->feature;
    }

    public function files(): array
    {
        return $this->files;
    }

    public function migrations(): array
    {
        return $this->migrations;
    }
}