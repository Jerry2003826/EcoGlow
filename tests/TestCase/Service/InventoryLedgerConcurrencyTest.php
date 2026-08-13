<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Inventory\InventoryLedger;
use App\Test\TestCase\Controller\Admin\AdminAuthTrait;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use PDO;
use PDOException;

/**
 * Concurrency coverage for inventory reservation row locks.
 */
class InventoryLedgerConcurrencyTest extends TestCase
{
    use AdminAuthTrait;

    /**
     * Two connections cannot reserve the last unit twice. The second waiter
     * hits the row lock, and after the first commit the stored procedure
     * refuses to let reserved exceed on-hand.
     *
     * @return void
     */
    public function testConcurrentReserveOfLastUnitDoesNotOversell(): void
    {
        $config = ConnectionManager::getConfig('test');
        $this->assertIsArray($config);

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['database'],
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ];
        $first = new PDO($dsn, $config['username'], $config['password'], $options);
        $second = new PDO($dsn, $config['username'], $config['password'], $options);
        $second->exec('SET innodb_lock_wait_timeout = 1');

        $sql = 'CALL sp_apply_inventory_change_in_transaction(?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $params = [2, 1, 'reservation', 0, 1, 'sales_order', 1, 'lock-test', 1];

        $first->beginTransaction();
        $reserve = $first->prepare($sql);
        $reserve->execute($params);
        $reserve->closeCursor();

        $blocked = false;
        try {
            $second->beginTransaction();
            $waiter = $second->prepare($sql);
            $waiter->execute($params);
            $this->fail('The second connection reserved stock while the first still held the row lock.');
        } catch (PDOException $exception) {
            $blocked = str_contains($exception->getMessage(), 'Lock wait timeout')
                || (int)$exception->errorInfo[1] === 1205;
            if ($second->inTransaction()) {
                $second->rollBack();
            }
        }

        $this->assertTrue($blocked, 'The second connection should wait on FOR UPDATE and time out.');

        $first->commit();

        $oversold = false;
        try {
            $second->beginTransaction();
            $retry = $second->prepare($sql);
            $retry->execute($params);
            $second->commit();
        } catch (PDOException $exception) {
            $oversold = str_contains($exception->getMessage(), 'Reserved inventory cannot exceed');
            if ($second->inTransaction()) {
                $second->rollBack();
            }
        }

        $this->assertTrue(
            $oversold,
            'After the first reservation commits, a second reserve of the last unit must fail.',
        );

        $ledger = new InventoryLedger();
        $this->assertSame(0, $ledger->quantityAvailable(2, 1));
        $row = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 2,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(1, (int)$row->quantity_on_hand);
        $this->assertSame(1, (int)$row->quantity_reserved);
    }
}
