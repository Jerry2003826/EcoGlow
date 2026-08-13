<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * InvoicesController issue-from-order and list.
 */
class InvoicesControllerTest extends TestCase
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
        $this->get('/admin/invoices');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/invoices');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/invoices');
        $this->assertResponseOk();
        $this->assertResponseContains('Invoices');
    }

    /**
     * Line items are copied from the order snapshot, not live catalogue prices.
     *
     * @return void
     */
    public function testCreateFromOrderSnapshotsLines(): void
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

        $variant = $this->fetchTable('ProductVariants')->get(1);
        $variant->price_cents = 99999;
        $this->fetchTable('ProductVariants')->saveOrFail($variant);

        $this->post('/admin/invoices/create-from-order/' . $order->id);
        $this->assertResponseCode(302);

        $invoice = $this->fetchTable('Invoices')->find()
            ->contain(['InvoiceItems'])
            ->firstOrFail();
        $this->assertSame(24900, (int)$invoice->grand_total_cents);
        $this->assertSame(24900, (int)$invoice->invoice_items[0]->unit_price_cents);
        $this->assertSame('EGL-MARLOW-01', $invoice->invoice_items[0]->sku_snapshot);

        $this->get('/admin/invoices/view/' . $invoice->id);
        $this->assertResponseOk();
        $this->assertResponseContains('Print / save as PDF');
        $this->assertResponseContains('GST inclusive');
        $this->assertResponseContains($invoice->invoice_number);
    }
}
