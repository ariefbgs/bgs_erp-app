<?php

namespace App\Services\Deployment;

interface DeploymentExecutionContextProviderInterface
{
    public function forRelease(
        int $releaseId
    ): DeploymentExecutionContext;
}