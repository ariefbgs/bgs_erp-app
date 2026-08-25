<?php

namespace Tests\Feature\Deployment;

use App\Http\Controllers\DeploymentController;
use App\Http\Requests\Deployment\UploadDeploymentPackageRequest;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\Authenticate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class DeploymentPackageUploadIntakeContractTest extends TestCase
{
    public function test_upload_uses_dedicated_form_request(): void
    {
        $method = new ReflectionMethod(
            DeploymentController::class,
            'upload'
        );

        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);

        $type = $parameters[0]->getType();

        $this->assertNotNull($type);

        $this->assertSame(
            UploadDeploymentPackageRequest::class,
            $type->getName()
        );
    }

    public function test_upload_request_requires_package_file(): void
    {
        $request = new UploadDeploymentPackageRequest();

        $rules = $request->rules();

        $this->assertArrayHasKey('package', $rules);

        $packageRules = is_array($rules['package'])
            ? $rules['package']
            : explode('|', $rules['package']);

        $this->assertContains('required', $packageRules);
        $this->assertContains('file', $packageRules);
    }

    public function test_upload_request_restricts_package_to_zip(): void
    {
        $request = new UploadDeploymentPackageRequest();

        $rules = $request->rules();

        $packageRules = is_array($rules['package'])
            ? $rules['package']
            : explode('|', $rules['package']);

        $serialized = implode('|', array_map(
            fn ($rule) => (string) $rule,
            $packageRules
        ));

        $this->assertMatchesRegularExpression(
            '/(mimes:zip|mimetypes:.*zip)/i',
            $serialized
        );
    }

    public function test_upload_request_has_explicit_size_limit(): void
    {
        $request = new UploadDeploymentPackageRequest();

        $rules = $request->rules();

        $packageRules = is_array($rules['package'])
            ? $rules['package']
            : explode('|', $rules['package']);

        $serialized = implode('|', array_map(
            fn ($rule) => (string) $rule,
            $packageRules
        ));

        $this->assertMatchesRegularExpression(
            '/max:\d+/i',
            $serialized
        );
    }

    public function test_upload_endpoint_rejects_missing_package(): void
    {
        $response = $this
            ->withoutMiddleware([
                Authenticate::class,
                CheckPermission::class,
            ])
            ->postJson('/deployment/upload');

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('package');
    }

    public function test_upload_endpoint_rejects_non_zip_package(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create(
            'release.txt',
            10,
            'text/plain'
        );

        $response = $this
            ->withoutMiddleware([
                Authenticate::class,
                CheckPermission::class,
            ])
            ->postJson('/deployment/upload', [
                'package' => $file,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors('package');
    }
}