<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Admin UsersController — configurable RBAC.
 */
class UsersControllerTest extends TestCase
{
    use AdminAuthTrait;
    use IntegrationTestTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/admin/users');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/users');
        $this->assertResponseCode(403);
    }

    /**
     * Standard staff cannot configure access.
     *
     * @return void
     */
    public function testIndexForbiddenForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/users');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/users');
        $this->assertResponseOk();
        $this->assertResponseContains('admin@example.com');
        $this->assertResponseContains('deny always wins over allow');
        $this->assertResponseContains('access.manage');
        $this->assertResponseContains('Permission matrix');
        $this->assertResponseContains('data-admin-fold');
        $this->assertResponseContains('Search staff accounts');
        $this->assertResponseContains('Search permissions');
        $this->assertResponseContains('Search overrides');
    }

    /**
     * Removing access.manage from the actor's own role is rejected and rolled back.
     *
     * @return void
     */
    public function testCannotRemoveOwnAccessManage(): void
    {
        $this->loginAs(1);
        $grants = [];
        foreach ($this->fetchTable('RolePermissions')->find() as $row) {
            if ((int)$row->role_id === 1 && (int)$row->permission_id === 1) {
                continue;
            }
            $grants[(int)$row->role_id][] = (int)$row->permission_id;
        }

        $this->post('/admin/users/update-matrix', ['grants' => $grants]);
        $this->assertResponseCode(302);
        $this->assertFlashMessage(
            'That change would remove your own access.manage permission, which would lock you out.',
        );
        $this->assertTrue($this->fetchTable('RolePermissions')->exists([
            'role_id' => 1,
            'permission_id' => 1,
        ]));
    }

    /**
     * A deny override on invoices.issue beats the Standard role grant.
     *
     * @return void
     */
    public function testDenyOverrideBlocksInvoicesForStandardStaff(): void
    {
        $this->loginAs(1);
        $this->post('/admin/users/set-override', [
            'user_id' => 2,
            'permission_id' => 17,
            'effect' => 'deny',
        ]);
        $this->assertResponseCode(302);

        $this->loginAs(2);
        $this->get('/admin/invoices');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testCannotDeactivateOwnAccount(): void
    {
        $this->loginAs(1);
        $this->post('/admin/users/toggle-active/1');
        $this->assertResponseCode(302);
        $this->assertSame(
            'active',
            (string)$this->fetchTable('Users')->get(1)->get('status'),
        );
    }
}
