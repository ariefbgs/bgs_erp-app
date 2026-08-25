<?php

namespace Tests\Feature\Deployment;

use App\Services\Deployment\DeploymentPackageRegistrationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DeploymentPackageRegistrationPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_register_persists_canonical_uploaded_release(): void
    {
        $releaseUuid = (string) Str::uuid();
        $releaseId = 'bgs-test-' . Str::lower(Str::random(12));
        $sha256 = hash('sha256', 'registration-persistence-test');

        $service = app(DeploymentPackageRegistrationService::class);

        $result = $service->register([
            'release_uuid' => $releaseUuid,
            'release_id' => $releaseId,
            'name' => 'BGS Test Release',
            'scope' => 'application',
            'version' => '1.0.0-test',
            'package_filename' => 'bgs-release.zip',
            'sha256' => $sha256,
        ]);

        $this->assertTrue(
            $result->isSuccess(),
            $result->message() ?? 'Registration must succeed.'
        );

        $this->assertNotNull($result->releaseId());

        $row = DB::table('application_releases')
            ->where('id', $result->releaseId())
            ->first();

        $this->assertNotNull($row);

        $this->assertSame($releaseUuid, $row->release_uuid);
        $this->assertSame($releaseId, $row->release_id);
        $this->assertSame('BGS Test Release', $row->name);
        $this->assertSame('application', $row->scope);
        $this->assertSame('1.0.0-test', $row->version);
        $this->assertSame('uploaded', $row->status);
        $this->assertSame('bgs-release.zip', $row->package_filename);
        $this->assertSame($sha256, $row->package_sha256);
    }

    public function test_register_rejects_duplicate_release_identity(): void
    {
        $releaseUuid = (string) Str::uuid();
        $releaseId = 'bgs-duplicate-' . Str::lower(Str::random(12));

        DB::table('application_releases')->insert([
            'release_uuid' => $releaseUuid,
            'release_id' => $releaseId,
            'name' => 'Existing Release',
            'scope' => 'application',
            'version' => '1.0.0-existing',
            'status' => 'uploaded',
            'package_filename' => 'existing.zip',
            'package_sha256' => hash('sha256', 'existing'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = DB::table('application_releases')->count();

        $service = app(DeploymentPackageRegistrationService::class);

        $result = $service->register([
            'release_uuid' => $releaseUuid,
            'release_id' => $releaseId,
            'name' => 'Duplicate Release',
            'scope' => 'application',
            'version' => '1.0.0-new',
            'package_filename' => 'new.zip',
            'sha256' => hash('sha256', 'new'),
        ]);

        $this->assertFalse(
            $result->isSuccess(),
            'Duplicate release identity must fail closed.'
        );

        $this->assertSame(
            $before,
            DB::table('application_releases')->count(),
            'Duplicate registration must not create another release.'
        );
    }

    public function test_register_rejects_invalid_canonical_input_without_persistence(): void
    {
        $before = DB::table('application_releases')->count();

        $service = app(DeploymentPackageRegistrationService::class);

        $result = $service->register([
            'release_uuid' => '',
            'release_id' => '',
            'name' => '',
            'scope' => 'invalid-scope',
            'version' => '',
            'package_filename' => 'invalid.zip',
            'sha256' => hash('sha256', 'invalid'),
        ]);

        $this->assertFalse($result->isSuccess());

        $this->assertSame(
            $before,
            DB::table('application_releases')->count()
        );
    }
}
