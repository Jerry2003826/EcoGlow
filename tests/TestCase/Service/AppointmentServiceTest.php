<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\ServiceRequest;
use App\Service\Inventory\InventoryLedger;
use App\Service\Services\AppointmentService;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Technician diary overlap: sequential and concurrent.
 */
class AppointmentServiceTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Customers',
        'app.ServiceTypes',
        'app.ServiceRequests',
        'app.ServiceAppointments',
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.InventoryMovements',
    ];

    /**
     * A second overlapping appointment for the same technician is refused.
     *
     * @return void
     */
    public function testOverlappingWindowIsRejected(): void
    {
        $first = $this->newRequest('SRV-OVERLAP-1');
        $second = $this->newRequest('SRV-OVERLAP-2');
        $service = new AppointmentService();
        $start = DateTime::parse('2026-08-20 09:00:00', 'Australia/Melbourne')->setTimezone('UTC');
        $end = DateTime::parse('2026-08-20 11:00:00', 'Australia/Melbourne')->setTimezone('UTC');

        $service->schedule($first, 1, $start, $end, 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already has an appointment');
        $overlapStart = DateTime::parse('2026-08-20 10:00:00', 'Australia/Melbourne')->setTimezone('UTC');
        $overlapEnd = DateTime::parse('2026-08-20 12:00:00', 'Australia/Melbourne')->setTimezone('UTC');
        $service->schedule($second, 1, $overlapStart, $overlapEnd, 1);
    }

    /**
     * Two connections cannot insert overlapping visits while the technician
     * row is locked.
     *
     * @return void
     */
    public function testConcurrentInsertCannotDoubleBookTechnician(): void
    {
        $firstRequest = $this->newRequest('SRV-LOCK-1');
        $secondRequest = $this->newRequest('SRV-LOCK-2');

        $config = ConnectionManager::getConfig('test');
        $this->assertIsArray($config);
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['database'],
        );
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $first = new PDO($dsn, $config['username'], $config['password'], $options);
        $second = new PDO($dsn, $config['username'], $config['password'], $options);
        $second->exec('SET innodb_lock_wait_timeout = 1');

        $first->beginTransaction();
        $first->query("SELECT GET_LOCK('svc_appt_staff_1', 10)")?->fetch();
        $lockUser = $first->prepare('SELECT id FROM users WHERE id = 1 FOR UPDATE');
        $lockUser->execute();
        $lockUser->fetchAll();

        $insert = $first->prepare(
            'INSERT INTO service_appointments
                (service_request_id, assigned_staff_user_id, starts_at, ends_at, status, created_by_user_id)
             VALUES (?, 1, ?, ?, \'confirmed\', 1)',
        );
        $insert->execute([
            $firstRequest->id,
            '2026-08-20 23:00:00',
            '2026-08-21 01:00:00',
        ]);

        $blocked = false;
        try {
            $second->beginTransaction();
            $waiter = $second->prepare('SELECT id FROM users WHERE id = 1 FOR UPDATE');
            $waiter->execute();
            $this->fail('The second connection read the technician row while it was locked.');
        } catch (PDOException $exception) {
            $blocked = str_contains($exception->getMessage(), 'Lock wait timeout')
                || (int)$exception->errorInfo[1] === 1205;
            if ($second->inTransaction()) {
                $second->rollBack();
            }
        }

        $this->assertTrue($blocked, 'The second connection should wait on FOR UPDATE and time out.');
        $first->commit();
        $first->query("SELECT RELEASE_LOCK('svc_appt_staff_1')")?->fetch();

        $service = new AppointmentService();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already has an appointment');
        $service->schedule(
            $secondRequest,
            1,
            DateTime::parse('2026-08-21 09:30:00', 'Australia/Melbourne')->setTimezone('UTC'),
            DateTime::parse('2026-08-21 11:30:00', 'Australia/Melbourne')->setTimezone('UTC'),
            1,
        );
    }

    /**
     * Recording a used part must deduct on-hand stock in the same transaction.
     *
     * @return void
     */
    public function testAddPartDecrementsOnHandAndStoresMovement(): void
    {
        $request = $this->newRequest('SRV-PART-1');
        $before = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(5, (int)$before->quantity_on_hand);

        (new AppointmentService(new InventoryLedger()))->addPart($request, 1, 2, 1);

        $after = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(3, (int)$after->quantity_on_hand);

        $part = $this->fetchTable('ServicePartsUsed')->find()
            ->where(['service_request_id' => $request->id])
            ->firstOrFail();
        $this->assertSame(2, (int)$part->quantity);
        $this->assertSame(1, (int)$part->inventory_location_id);
        $this->assertGreaterThan(0, (int)$part->inventory_movement_id);
        $this->assertTrue($this->fetchTable('InventoryMovements')->exists([
            'id' => $part->inventory_movement_id,
            'movement_type' => 'service_issue',
            'on_hand_delta' => -2,
        ]));
    }

    /**
     * Parts cannot be recorded when on-hand stock is insufficient.
     *
     * @return void
     */
    public function testAddPartRejectsWhenStockIsShort(): void
    {
        $request = $this->newRequest('SRV-PART-SHORT');
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not enough stock');
        (new AppointmentService(new InventoryLedger()))->addPart($request, 1, 99, 1);
    }

    /**
     * @param string $number Request number.
     * @return \App\Model\Entity\ServiceRequest
     */
    private function newRequest(string $number): ServiceRequest
    {
        $requests = $this->fetchTable('ServiceRequests');
        $request = $requests->newEmptyEntity();
        $request->set('request_number', $number);
        $request->set('customer_id', 2);
        $request->set('service_type_id', 1);
        $request->set('contact_name', 'Casey Aitken');
        $request->set('contact_email', 'customer-a@example.com');
        $request->set('address_line1', '10 Flinders Lane');
        $request->set('suburb', 'Melbourne');
        $request->set('state', 'VIC');
        $request->set('postcode', '3000');
        $request->set('country_code', 'AU');
        $request->set('issue_description', 'Install a floor lamp.');
        $request->set('attachment_urls', []);
        $request->set('priority', 'normal');
        $request->set('status', 'new');

        return $requests->saveOrFail($request);
    }
}
