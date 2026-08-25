<?php

namespace Tests\Feature\Deployment;

use App\Http\Controllers\DeploymentController;
use App\Http\Requests\Deployment\RollbackDeploymentReleaseRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

final class DeploymentRollbackSafetyContractTest extends TestCase
{
    use DatabaseTransactions;

    public function test_rollback_uses_dedicated_form_request(): void
    {
        $method = new ReflectionMethod(
            DeploymentController::class,
            'rollback'
        );

        $parameter = $method->getParameters()[0] ?? null;

        $this->assertNotNull($parameter);
        $this->assertSame(
            RollbackDeploymentReleaseRequest::class,
            $parameter->getType()?->getName()
        );
    }

    public function test_unimplemented_rollback_fails_closed_without_state_mutation(): void
    {
        $id = DB::table('application_releases')->insertGetId([
            'release_uuid' => (string) Str::uuid(),
            'release_id' => 'BGS-ROLLBACK-' . Str::random(10),
            'name' => 'Rollback Safety Contract',
            'scope' => 'application',
            'version' => '1.0.0-test',
            'status' => 'installed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $beforeHistory = DB::table('deployment_history')->count();

        $this
            ->withoutMiddleware()
            ->postJson(route('deployment.rollback'), [
                'application_release_id' => $id,
            ])
            ->assertStatus(501);

        $this->assertSame(
            'installed',
            DB::table('application_releases')
                ->where('id', $id)
                ->value('status')
        );

        $this->assertSame(
            $beforeHistory,
            DB::table('deployment_history')->count()
        );
    }
}
