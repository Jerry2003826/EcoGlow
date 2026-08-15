<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * ReportsController smoke tests.
 */
class ReportsControllerTest extends AdminAppTestCase
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

    /**
     * Fully refunded orders must not inflate gross sales or estimated profit.
     *
     * @return void
     */
    public function testRefundedOrderIsExcludedFromGrossSalesAndProfit(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_PHONE,
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 1],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()->firstOrFail();
        $order->payment_status = 'refunded';
        $this->fetchTable('SalesOrders')->saveOrFail($order);

        $today = Date::now('Australia/Melbourne')->format('Y-m-d');
        $connection = $this->fetchTable('SalesOrders')->getConnection();
        $dashboard = $connection->execute(
            'SELECT COALESCE(SUM(gross_sales_cents), 0) AS gross_sales_cents
               FROM v_business_dashboard_daily
              WHERE business_date = ?',
            [$today],
        )->fetch('assoc');
        $this->assertSame(0, (int)($dashboard['gross_sales_cents'] ?? -1));

        $profit = $connection->execute(
            'SELECT COALESCE(SUM(estimated_gross_profit_cents), 0) AS estimated_gross_profit_cents
               FROM v_order_profitability
              WHERE sales_order_id = ?',
            [$order->id],
        )->fetch('assoc');
        $this->assertSame(0, (int)($profit['estimated_gross_profit_cents'] ?? -1));

        $lifetime = $connection->execute(
            'SELECT lifetime_order_value_cents
               FROM v_customer_360_summary
              WHERE customer_id = ?',
            [$order->customer_id],
        )->fetch('assoc');
        $this->assertSame(0, (int)($lifetime['lifetime_order_value_cents'] ?? -1));
    }

    /**
     * Invalid custom dates must not 500.
     *
     * @return void
     */
    public function testCustomRangeWithInvalidDatesStaysOnToday(): void
    {
        $this->loginAs(1);
        $this->get('/admin/reports?preset=custom&from=not-a-date&to=also-bad');
        $this->assertResponseOk();
        $this->assertResponseContains('Sales (GST inclusive)');
    }
}
