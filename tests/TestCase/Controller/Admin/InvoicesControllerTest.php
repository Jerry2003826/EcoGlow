<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\TestSuite\IntegrationTestTrait;

/**
 * InvoicesController issue-from-order and list.
 */
class InvoicesControllerTest extends AdminAppTestCase
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

    /**
     * Fully or partially refunded orders must not receive a new unpaid invoice.
     *
     * @return void
     */
    public function testCreateFromOrderRejectsRefundedOrder(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_PHONE,
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 1],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();
        $this->fetchTable('SalesOrders')->getConnection()->execute(
            "UPDATE sales_orders SET payment_status = 'refunded' WHERE id = ?",
            [$order->id],
        );
        $before = $this->fetchTable('Invoices')->find()->count();

        $this->post('/admin/invoices/create-from-order/' . $order->id);
        $this->assertResponseCode(302);
        $this->assertFlashMessage('A refunded order cannot be invoiced.');
        $this->assertSame($before, $this->fetchTable('Invoices')->find()->count());
    }

    /**
     * Manual invoice payments must settle the order and use unique payment ids.
     *
     * @return void
     */
    public function testRecordPaymentMarksOrderPaidAndUsesUniqueIds(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_PHONE,
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 1],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()
            ->orderBy(['id' => 'DESC'])
            ->firstOrFail();
        $this->post('/admin/invoices/create-from-order/' . $order->id);
        $invoice = $this->fetchTable('Invoices')->find()
            ->where(['sales_order_id' => $order->id])
            ->firstOrFail();

        $this->post('/admin/invoices/record-payment/' . $invoice->id, ['amount' => '100.00']);
        $this->post('/admin/invoices/record-payment/' . $invoice->id, ['amount' => '149.00']);
        $this->assertResponseCode(302);

        $payments = $this->fetchTable('Payments')->find()
            ->where([
                'sales_order_id' => $order->id,
                'provider' => 'manual',
            ])
            ->all()
            ->toList();
        $this->assertCount(2, $payments);
        $this->assertNotSame(
            (string)$payments[0]->provider_payment_id,
            (string)$payments[1]->provider_payment_id,
        );

        $invoice = $this->fetchTable('Invoices')->get($invoice->id);
        $this->assertSame('paid', (string)$invoice->status);
        $this->assertSame(0, (int)$invoice->balance_due_cents);
        $order = $this->fetchTable('SalesOrders')->get($order->id);
        $this->assertSame('paid', (string)$order->payment_status);
    }
}
