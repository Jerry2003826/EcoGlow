<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * ReportsController smoke tests.
 */
class ReportsControllerTest extends TestCase
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
        $this->get('/admin/reports');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/reports');
        $this->assertResponseCode(403);
    }

    /**
     * Standard staff no longer has reports.view.
     *
     * @return void
     */
    public function testIndexForbiddenForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/reports');
        $this->assertResponseCode(403);
    }

    /**
     * Empty ranges render as 0, with the required GST and profit wording.
     *
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/reports');
        $this->assertResponseOk();
        $this->assertResponseNotContains('estimated gross profit');
        $this->assertResponseContains('GST inclusive');
        $this->assertResponseContains('Sales (GST inclusive)');
    }

    /**
     * Profit and COGS stay on the financial action.
     *
     * @return void
     */
    public function testFinancialOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/reports/financial');
        $this->assertResponseOk();
        $this->assertResponseContains('estimated gross profit');
    }

    /**
     * @return void
     */
    public function testFinancialForbiddenForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/reports/financial');
        $this->assertResponseCode(403);
    }
}
