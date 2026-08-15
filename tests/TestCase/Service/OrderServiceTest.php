<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\SalesOrder;
use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;

/**
 * Status changes must re-read the locked order row.
 */
class OrderServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.SalesOrders',
        'app.SalesOrderItems',
        'app.OrderStatusHistory',
        'app.StockReservations',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.InventoryMovements',
        'app.Products',
        'app.ProductVariants',
    ];

    /**
     * A stale in-memory status cannot win after another transition commits.
     *
     * @return void
     */
    public function testChangeStatusRevalidatesLockedCurrentStatus(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute(
            "INSERT INTO sales_orders
                (order_number, status, payment_status, fulfilment_method, currency,
                 source_channel, order_type, version_number, metadata)
             VALUES ('SO-RACE-1', 'processing', 'paid', 'shipping', 'AUD',
                     'phone', 'retail', 1, '{}')",
        );
        $id = (int)$connection->getDriver()->lastInsertId();
        $orders = $this->fetchTable('SalesOrders');
        $stale = $orders->get($id);
        $this->assertSame(SalesOrder::STATUS_PROCESSING, $stale->status);

        $service = new OrderService(new InventoryLedger());
        $service->changeStatus($stale, SalesOrder::STATUS_CANCELLED, 1, 'first writer');
        $this->assertSame(SalesOrder::STATUS_CANCELLED, (string)$orders->get($id)->status);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot move an order from cancelled to dispatched.');
        $service->changeStatus($stale, SalesOrder::STATUS_DISPATCHED, 1, 'stale writer');
    }
}
