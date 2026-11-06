<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HostingerProductionReadinessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_registration_has_no_get_or_post_route(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get('/login')->assertOk();
    }

    public function test_admin_bootstrap_requires_interactive_input(): void
    {
        $before = User::count();
        $this->artisan('bgs:bootstrap-admin', ['email' => 'synthetic@example.invalid', 'name' => 'Synthetic', '--no-interaction' => true])
            ->assertExitCode(1);
        $this->assertSame($before, User::count());
    }

    public function test_first_admin_has_a_hashed_password_and_repeat_is_refused(): void
    {
        $this->assertSame(0, User::count());
        config(['bgs_access.restricted_full_user_ids' => []]);
        $this->artisan('bgs:bootstrap-admin', ['email' => 'synthetic@example.invalid', 'name' => 'Synthetic'])
            ->expectsQuestion('Password (minimum 8 characters)', 'Synthetic-Only-123')
            ->expectsQuestion('Confirm password', 'Synthetic-Only-123')
            ->assertExitCode(0);
        $user = User::where('email', 'synthetic@example.invalid')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue(Hash::check('Synthetic-Only-123', $user->password));
        $before = $user->password;
        $this->artisan('bgs:bootstrap-admin', ['email' => 'other@example.invalid', 'name' => 'Other'])->assertExitCode(1);
        $this->assertSame(1, User::count());
        $this->assertSame($before, $user->fresh()->password);
    }

    public function test_mismatched_password_does_not_create_a_user(): void
    {
        $this->artisan('bgs:bootstrap-admin', ['email' => 'synthetic@example.invalid', 'name' => 'Synthetic'])
            ->expectsQuestion('Password (minimum 8 characters)', 'Synthetic-Only-123')
            ->expectsQuestion('Confirm password', 'Different-123')->assertExitCode(1);
        $this->assertSame(0, User::count());
    }

    public function test_preflight_reports_blockers_without_database_mutation(): void
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query->sql; });
        $this->assertSame(1, Artisan::call('bgs:production-check', ['expected_sha' => str_repeat('a', 40)]));
        $report = json_decode(Artisan::output(), true, 64, JSON_THROW_ON_ERROR);
        $this->assertSame('BLOCKED', $report['status']);
        $this->assertFalse($report['checks']['environment_production']);
        $this->assertFalse($report['checks']['release_identity']);
        $this->assertTrue($report['checks']['registration_disabled']);
        $this->assertSame(0, $report['database_writes']);
        $this->assertFalse($report['go_live_approved']);
        foreach ($queries as $sql) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|create|drop|truncate)\b/i', $sql);
        }
    }
}
