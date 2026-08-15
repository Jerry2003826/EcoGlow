<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;

/**
 * CustomersController list and 360 view.
 */
class CustomersControllerTest extends AdminAppTestCase
{
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
        $this->get('/admin/customers');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/customers');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/customers');
        $this->assertResponseOk();
        $this->assertResponseContains('Alex Nguyen');
    }

    /**
     * Denying customers.view masks phone and email even for Master.
     *
     * @return void
     */
    public function testViewMasksContactWithoutCustomersView(): void
    {
        $overrides = $this->fetchTable('UserPermissionOverrides');
        $deny = $overrides->newEmptyEntity();
        $deny->user_id = 1;
        $deny->permission_id = 2;
        $deny->effect = 'deny';
        $overrides->saveOrFail($deny);

        $this->loginAs(1);
        $this->get('/admin/customers/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('04** *** *99');
        $this->assertResponseContains('a***@example.com');
        $this->assertResponseContains('Contact details are permission-protected');
        $this->assertResponseNotContains('0400000099');
    }
}
