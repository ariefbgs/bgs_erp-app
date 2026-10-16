<?php
namespace Tests\Unit;
use App\Models\User;
use App\Models\UserAccessSetting;
use Tests\TestCase;
class UserAccessRulesTest extends TestCase {
    private function account(string $role, bool $features, bool $dashboard): User {
        $user = new User; $user->role = $role;
        $setting = new UserAccessSetting;
        $setting->full_features = $features; $setting->dashboard_access = $dashboard;
        $user->setRelation('accessSetting', $setting); return $user;
    }
    public function test_user_has_full_features_without_dashboard(): void {
        $user = $this->account('staff', true, false);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->canUseAdminFeatures());
        $this->assertTrue($user->canDelete());
        $this->assertTrue($user->hasPermission('deployment_view'));
        $this->assertFalse($user->canAccessDashboard());
    }
    public function test_admin_dashboard_can_be_disabled(): void {
        $user = $this->account('admin', true, false);
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->canAccessDashboard());
    }
    public function test_restricted_user_does_not_gain_admin_features(): void {
        $user = $this->account('staff', false, false);
        $this->assertFalse($user->canUseAdminFeatures());
        $this->assertFalse($user->canDelete());
        $this->assertFalse($user->canAccessDashboard());
    }
    public function test_configured_restricted_full_account_excludes_deployment_backup_and_dashboard(): void {
        config(['bgs_access.restricted_full_user_ids' => [4]]);
        $user = $this->account('staff', true, true);
        $user->id = 4;
        $this->assertTrue($user->canUseAdminFeatures());
        $this->assertTrue($user->canManageUserAccess());
        $this->assertFalse($user->canAccessDashboard());
        $this->assertFalse($user->canAccessBackup());
        $this->assertFalse($user->hasPermission('deployment_view'));
        $this->assertFalse($user->hasPermission('deployment_install'));
        $this->assertFalse($user->hasPermission('backup_restore'));
    }
}
