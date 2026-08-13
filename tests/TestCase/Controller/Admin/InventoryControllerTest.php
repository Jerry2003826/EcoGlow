<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * InventoryController smoke and adjust tests.
 */
class InventoryControllerTest extends TestCase
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
        $this->get('/admin/inventory');
        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * @return void
     */
    public function testIndexForbiddenWithoutPermission(): void
    {
        $this->loginAs(3);
        $this->get('/admin/inventory');
        $this->assertResponseCode(403);
    }

    /**
     * Standard staff can view inventory but not the adjust form.
     *
     * @return void
     */
    public function testIndexOkForStandardStaffWithoutAdjustForm(): void
    {
        $this->loginAs(2);
        $this->get('/admin/inventory');
        $this->assertResponseOk();
        $this->assertResponseContains('EGL-MARLOW-01');
        $this->assertResponseNotContains('name="reason"');
    }

    /**
     * @return void
     */
    public function testIndexOkForMaster(): void
    {
        $this->loginAs(1);
        $this->get('/admin/inventory');
        $this->assertResponseOk();
        $this->assertResponseContains('Needs reorder');
        $this->assertResponseContains('name="reason"');
    }

    /**
     * @return void
     */
    public function testAdjustRequiresReason(): void
    {
        $this->loginAs(1);
        $this->post('/admin/inventory/adjust', [
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
            'quantity' => 2,
            'reason' => '',
        ]);
        $this->assertResponseCode(302);
        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(5, (int)$balance->quantity_on_hand);
    }

    /**
     * @return void
     */
    public function testAdjustReceiptIncreasesOnHandViaProcedure(): void
    {
        $this->loginAs(1);
        $this->post('/admin/inventory/adjust', [
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
            'quantity' => 3,
            'reason' => 'receipt',
            'note' => 'Supplier delivery',
        ]);
        $this->assertResponseCode(302);
        $balance = $this->fetchTable('InventoryBalances')->get([
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
        ]);
        $this->assertSame(8, (int)$balance->quantity_on_hand);
        $this->assertTrue($this->fetchTable('InventoryMovements')->exists([
            'product_variant_id' => 1,
            'movement_type' => 'receipt',
            'on_hand_delta' => 3,
        ]));
    }

    /**
     * @return void
     */
    public function testAdjustForbiddenForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->post('/admin/inventory/adjust', [
            'product_variant_id' => 1,
            'inventory_location_id' => 1,
            'quantity' => 1,
            'reason' => 'receipt',
        ]);
        $this->assertResponseCode(403);
    }
}
