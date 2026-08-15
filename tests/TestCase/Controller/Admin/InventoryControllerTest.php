<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Cake\TestSuite\IntegrationTestTrait;

/**
 * InventoryController smoke and adjust tests.
 */
class InventoryControllerTest extends AdminAppTestCase
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
     * Standard staff no longer has inventory.view after the batch-2 tightening.
     *
     * @return void
     */
    public function testIndexForbiddenForStandardStaff(): void
    {
        $this->loginAs(2);
        $this->get('/admin/inventory');
        $this->assertResponseCode(403);
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
        $this->assertResponseContains('Adjust stock');
        $this->assertResponseContains('name="reason"');
        $this->assertResponseContains('Stock balances');
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
