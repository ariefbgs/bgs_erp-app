<?php

namespace Tests\Feature\Deployment;

use App\Http\Controllers\DeploymentController;
use App\Http\Requests\Deployment\InstallDeploymentReleaseRequest;
use ReflectionMethod;
use Tests\TestCase;

final class DeploymentInstallRequestContractTest extends TestCase
{
    public function test_install_uses_dedicated_form_request(): void
    {
        $method = new ReflectionMethod(
            DeploymentController::class,
            'install'
        );

        $parameter = $method->getParameters()[0] ?? null;

        $this->assertNotNull($parameter);
        $this->assertSame(
            InstallDeploymentReleaseRequest::class,
            $parameter->getType()?->getName()
        );
    }

    public function test_install_request_requires_positive_application_release_id(): void
    {
        $request = new InstallDeploymentReleaseRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('application_release_id', $rules);
        $this->assertContains('required', $rules['application_release_id']);
        $this->assertContains('integer', $rules['application_release_id']);
        $this->assertContains('min:1', $rules['application_release_id']);
        $this->assertContains(
            'exists:application_releases,id',
            $rules['application_release_id']
        );
    }

    public function test_install_endpoint_rejects_missing_release_identity(): void
    {
        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'))
            ->assertUnprocessable();
    }

    public function test_install_endpoint_rejects_unknown_release_identity(): void
    {
        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.install'), [
                'application_release_id' => PHP_INT_MAX,
            ])
            ->assertUnprocessable();
    }
}
