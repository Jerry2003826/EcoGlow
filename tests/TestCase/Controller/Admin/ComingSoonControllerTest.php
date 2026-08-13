<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ComingSoonController smoke tests.
 */
class ComingSoonControllerTest extends TestCase
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
    }

    /**
     * @return void
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/admin/coming-soon/appointments');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/coming-soon/appointments');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForStaff(): void
    {
        $this->loginAs(1);
        $this->get('/admin/coming-soon/appointments');
        $this->assertResponseOk();
        $this->assertResponseContains('Schedule licensed installation');
        $this->assertResponseNotContains('Please check back later');
    }

    /**
     * @return void
     */
    public function testUnknownModuleIsNotFound(): void
    {
        $this->loginAs(1);
        $this->get('/admin/coming-soon/not-a-module');
        $this->assertResponseCode(404);
    }
}
