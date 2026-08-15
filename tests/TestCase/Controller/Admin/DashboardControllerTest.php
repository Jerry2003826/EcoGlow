<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;

/**
 * DashboardController smoke tests.
 */
class DashboardControllerTest extends AdminAppTestCase
{
    use IntegrationTestTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/admin');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin');
        $this->assertResponseOk();
        $this->assertResponseContains('Dashboard');
        $this->assertResponseContains('Orders today');
        $this->assertResponseContains('Needs attention');
        $this->assertResponseContains('Recent transactions');
    }
}
