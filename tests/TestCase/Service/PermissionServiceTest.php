<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Authorization\PermissionService;
use App\Test\TestCase\Controller\Admin\AdminAuthTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * Data-driven permission resolution, including deny-wins overrides.
 */
class PermissionServiceTest extends TestCase
{
    use AdminAuthTrait;

    /**
     * @return void
     */
    public function testMasterHasEverySeededPermission(): void
    {
        $service = new PermissionService();
        $this->assertTrue($service->has(1, 'access.manage'));
        $this->assertTrue($service->has(1, 'inventory.adjust'));
        $this->assertTrue($service->has(1, 'orders.create'));
        $this->assertTrue($service->hasAny(1));
    }

    /**
     * Standard staff matches the seeded four operational verbs plus the
     * extra keys stored in role_permissions — not a hardcoded PHP list.
     *
     * @return void
     */
    public function testStandardStaffHasSeededKeysOnly(): void
    {
        $service = new PermissionService();
        $this->assertTrue($service->has(2, 'orders.create'));
        $this->assertTrue($service->has(2, 'orders.dispatch'));
        $this->assertTrue($service->has(2, 'invoices.issue'));
        $this->assertTrue($service->has(2, 'refunds.process'));
        $this->assertFalse($service->has(2, 'inventory.adjust'));
        $this->assertFalse($service->has(2, 'access.manage'));
    }

    /**
     * @return void
     */
    public function testUserWithoutRolesHasNothing(): void
    {
        $service = new PermissionService();
        $this->assertFalse($service->hasAny(3));
        $this->assertFalse($service->has(3, 'orders.create'));
    }

    /**
     * Deny overrides beat both role grants and allow overrides.
     *
     * @return void
     */
    public function testDenyOverrideBeatsAllowAndRoleGrant(): void
    {
        $overrides = $this->fetchTable('UserPermissionOverrides');
        $now = DateTime::now('UTC');
        $deny = $overrides->newEmptyEntity();
        $deny->user_id = 1;
        $deny->permission_id = 13;
        $deny->effect = 'deny';
        $deny->starts_at = $now;
        $overrides->saveOrFail($deny);

        $allow = $overrides->newEmptyEntity();
        $allow->user_id = 1;
        $allow->permission_id = 13;
        $allow->effect = 'allow';
        $allow->starts_at = $now;
        $overrides->saveOrFail($allow);

        $service = new PermissionService();
        $this->assertFalse($service->has(1, 'inventory.adjust'));
        $this->assertTrue($service->has(1, 'orders.create'));
    }
}
