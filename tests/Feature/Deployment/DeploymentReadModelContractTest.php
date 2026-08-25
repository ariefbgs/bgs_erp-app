<?php

namespace Tests\Feature\Deployment;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DeploymentReadModelContractTest extends TestCase
{
    use DatabaseTransactions;

    private int $releaseId;
    private string $releaseIdentity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(
            'erp_app_testing',
            DB::connection()->getDatabaseName()
        );

        $this->releaseIdentity =
            'BGS-READ-MODEL-' . strtoupper(bin2hex(random_bytes(4)));

        $this->releaseId = DB::table('application_releases')->insertGetId([
            'release_uuid' => (string) Str::uuid(),
            'release_id' => $this->releaseIdentity,
            'name' => $this->releaseIdentity,
            'scope' => 'application',
            'version' => '1.0.0-read-model',
            'status' => 'installed',
            'package_filename' => 'read-model.zip',
            'package_sha256' => hash('sha256', 'read-model'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('deployment_history')->insert([
            'deployment_uuid' => (string) Str::uuid(),
            'application_release_id' => $this->releaseId,
            'action' => 'install',
            'status' => 'success',
            'started_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_index_renders_paginated_canonical_releases(): void
    {
        $response = $this
            ->withoutMiddleware()
            ->get(route('deployment.index'));

        $response
            ->assertOk()
            ->assertViewIs('deployment.index')
            ->assertViewHas('releases')
            ->assertSee($this->releaseIdentity)
            ->assertSee('installed');

        $this->assertSame(
            8,
            $response->viewData('releases')->perPage()
        );
    }

    public function test_history_renders_release_linked_audit_rows(): void
    {
        $this
            ->withoutMiddleware()
            ->get(route('deployment.history'))
            ->assertOk()
            ->assertViewIs('deployment.history')
            ->assertViewHas('history')
            ->assertSee($this->releaseIdentity)
            ->assertSee('Install')
            ->assertSee('Success');
    }

    public function test_index_ui_preserves_mutation_permission_boundaries(): void
    {
        $source = file_get_contents(
            resource_path('views/deployment/index.blade.php')
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('deployment_upload', $source);
        $this->assertStringContainsString('deployment_install', $source);
        $this->assertStringContainsString("route('deployment.upload')", $source);
        $this->assertStringContainsString("route('deployment.install')", $source);
        $this->assertStringNotContainsString("route('deployment.rollback')", $source);
        $this->assertStringContainsString('application_release_id', $source);
    }
}
