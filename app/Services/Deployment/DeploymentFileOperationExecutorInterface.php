<?php

namespace App\Services\Deployment;

interface DeploymentFileOperationExecutorInterface
{
    public function apply(array $operation): array;
}