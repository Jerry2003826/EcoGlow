<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Model\Entity\SalesOrder;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * OrdersController tests including reservation on create.
 */
class OrdersControllerTest extends TestCase
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
        $this->get('/admin/orders');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/orders');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/orders');
        $this->assertResponseOk();
        $this->assertResponseContains('Record order');
    }

    /**
     * @return void
     */
    public function testAddRequiresAuthentication(): void
    {
        $this->get('/admin/orders/add');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testAddForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/orders/add');
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testAddFormOk(): void
    {
        $this->loginAs(1);
        $this->get('/admin/orders/add');
        $this->assertResponseOk();
        $this->assertResponseContains('Source channel');
        $this->assertResponseContains('Promised delivery date');
        $this->assertResponseContains('EGL-MARLOW-01');
    }

    /**
     * Creating an order snapshots the variant and reserves available stock.
     *
     * @return void
     */
    public function testCreateReservesStockAndSnapshotsPrice(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_PHONE,
            'external_source_reference' => 'CALL-99',
            'promised_delivery_date' => '2026-08-20',
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 2],
            ],
        ]);
        $this->assertResponseCode(302);

        $order = $this->fetchTable('SalesOrders')->find()
            ->contain(['SalesOrderItems'])
            ->first();
        $this->assertNotNull($order);
        $this->assertSame(SalesOrder::CHANNEL_PHONE, $order->source_channel);
        $this->assertSame('CALL-99', $order->external_source_reference);
        $this->assertSame(24900 * 2, (int)$order->grand_total_cents);
        $this->assertSame(24900, (int)$order->sales_order_items[0]->unit_price_cents);
        $this->assertSame('EGL-MARLOW-01', $order->sales_order_items[0]->sku_snapshot);
        $this->assertSame(11000, (int)$order->sales_order_items[0]->cost_snapshot_cents);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(5, (int)$balance->quantity_on_hand);
        $this->assertSame(2, (int)$balance->quantity_reserved);
        $this->assertSame(3, (int)$balance->quantity_available);

        $this->assertTrue($this->fetchTable('InventoryMovements')->exists([
            'product_variant_id' => 1,
            'movement_type' => 'reservation',
            'reserved_delta' => 2,
        ]));
        $this->assertTrue($this->fetchTable('StockReservations')->exists([
            'sales_order_id' => $order->id,
            'quantity' => 2,
            'status' => 'active',
        ]));
    }

    /**
     * Short stock is allowed: the order saves and only available units reserve.
     *
     * @return void
     */
    public function testCreateAllowsBackorderWhenStockIsShort(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_first_name' => 'Sam',
            'customer_email' => 'sam@example.com',
            'source_channel' => SalesOrder::CHANNEL_EMAIL,
            'lines' => [
                ['product_variant_id' => 2, 'quantity' => 4],
            ],
        ]);
        $this->assertResponseCode(302);

        $order = $this->fetchTable('SalesOrders')->find()
            ->contain(['SalesOrderItems'])
            ->first();
        $this->assertNotNull($order);
        $this->assertNotEmpty($order->metadata['stock_warnings'] ?? null);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 2,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(1, (int)$balance->quantity_on_hand);
        $this->assertSame(1, (int)$balance->quantity_reserved);
        $this->assertSame(0, (int)$balance->quantity_available);
    }

    /**
     * @return void
     */
    public function testViewAndStatusAdvance(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_IN_STORE,
            'promised_delivery_date' => '2026-08-10',
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 1],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()->firstOrFail();

        $this->get('/admin/orders/view/' . $order->id);
        $this->assertResponseOk();
        $this->assertResponseContains($order->order_number);
        $this->assertResponseContains('Promised delivery');

        $this->post('/admin/orders/update-status/' . $order->id, [
            'status' => SalesOrder::STATUS_PROCESSING,
        ]);
        $this->assertResponseCode(302);
        $this->assertSame(
            SalesOrder::STATUS_PROCESSING,
            $this->fetchTable('SalesOrders')->get($order->id)->status,
        );
        $this->assertTrue($this->fetchTable('OrderStatusHistory')->exists([
            'sales_order_id' => $order->id,
            'to_status' => SalesOrder::STATUS_PROCESSING,
            'changed_by_user_id' => 1,
        ]));
    }

    /**
     * Cancelling an order releases its reservations so available stock returns.
     *
     * @return void
     */
    public function testCancelReleasesReservedStock(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_PHONE,
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 2],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()->firstOrFail();

        $this->post('/admin/orders/update-status/' . $order->id, [
            'status' => SalesOrder::STATUS_CANCELLED,
        ]);
        $this->assertResponseCode(302);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(5, (int)$balance->quantity_on_hand);
        $this->assertSame(0, (int)$balance->quantity_reserved);
        $this->assertSame(5, (int)$balance->quantity_available);
        $this->assertTrue($this->fetchTable('StockReservations')->exists([
            'sales_order_id' => $order->id,
            'status' => 'released',
        ]));
    }

    /**
     * Dispatch deducts on-hand and reserved together.
     *
     * @return void
     */
    public function testDispatchConsumesReservedStock(): void
    {
        $this->loginAs(1);
        $this->post('/admin/orders/add', [
            'customer_id' => 1,
            'source_channel' => SalesOrder::CHANNEL_IN_STORE,
            'lines' => [
                ['product_variant_id' => 1, 'quantity' => 2],
            ],
        ]);
        $order = $this->fetchTable('SalesOrders')->find()->firstOrFail();

        $this->post('/admin/orders/update-status/' . $order->id, [
            'status' => SalesOrder::STATUS_PROCESSING,
        ]);
        $this->post('/admin/orders/update-status/' . $order->id, [
            'status' => SalesOrder::STATUS_DISPATCHED,
        ]);
        $this->assertResponseCode(302);

        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(3, (int)$balance->quantity_on_hand);
        $this->assertSame(0, (int)$balance->quantity_reserved);
        $this->assertSame(3, (int)$balance->quantity_available);
        $this->assertTrue($this->fetchTable('StockReservations')->exists([
            'sales_order_id' => $order->id,
            'status' => 'consumed',
        ]));
    }
}
