<?php

namespace App\Services\Deployment;

final class DeploymentExecutionContext
{
    private string $packagePath;
    private string $stagingPath;
    private string $applicationRoot;
    private string $recoveryPath;
    private ?string $expectedPackageSha256;
    private ?array $expectedManifestIdentity;

    public function __construct(
        string $packagePath,
        string $stagingPath,
        string $applicationRoot,
        string $recoveryPath,
        ?string $expectedPackageSha256 = null,
        ?array $expectedManifestIdentity = null
    ) {
        $this->packagePath = $packagePath;
        $this->stagingPath = $stagingPath;
        $this->applicationRoot = $applicationRoot;
        $this->recoveryPath = $recoveryPath;
        $this->expectedPackageSha256 = $expectedPackageSha256;
        $this->expectedManifestIdentity = $expectedManifestIdentity;
    }

    public function packagePath(): string
    {
        return $this->packagePath;
    }

    public function stagingPath(): string
    {
        return $this->stagingPath;
    }

    public function applicationRoot(): string
    {
        return $this->applicationRoot;
    }

    public function recoveryPath(): string
    {
        return $this->recoveryPath;
    }

    public function expectedPackageSha256(): ?string
    {
        return $this->expectedPackageSha256;
    }

    public function expectedManifestIdentity(): ?array
    {
        return $this->expectedManifestIdentity;
    }
}
