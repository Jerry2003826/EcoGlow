<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Storefront add-to-basket flashes.
 */
class CartsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Customers',
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.Carts',
        'app.CartItems',
        'app.SiteSettings',
        'app.ContactMessages',
    ];

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
    public function testAddSucceedsWhenStockExists(): void
    {
        $this->post('/cart/add', [
            'product_variant_id' => 1,
            'quantity' => 1,
        ]);
        $this->assertRedirect('/cart');
        $this->assertFlashMessage('Added to your basket.');
        $this->assertSame(1, $this->fetchTable('CartItems')->find()->count());
    }

    /**
     * @return void
     */
    public function testAddFlashesWhenOutOfStock(): void
    {
        $this->fetchTable('InventoryBalances')->getConnection()->execute(
            'UPDATE inventory_balances SET quantity_on_hand = 0 WHERE product_variant_id = 1',
        );
        $this->post('/cart/add', [
            'product_variant_id' => 1,
            'quantity' => 1,
        ]);
        $this->assertRedirect('/cart');
        $this->assertFlashMessage('This item is temporarily out of stock.');
        $this->assertSame(0, $this->fetchTable('CartItems')->find()->count());
    }

    /**
     * @return void
     */
    public function testAddFlashesWhenVariantMissing(): void
    {
        $this->post('/cart/add', [
            'product_variant_id' => 9999,
            'quantity' => 1,
        ]);
        $this->assertRedirect('/cart');
        $this->assertFlashMessage('That product is no longer in the catalogue.');
        $this->assertSame(0, $this->fetchTable('CartItems')->find()->count());
    }

    /**
     * @return void
     */
    public function testAddFlashesWhenQuantityInvalid(): void
    {
        $this->post('/cart/add', [
            'product_variant_id' => 1,
            'quantity' => 0,
        ]);
        $this->assertRedirect('/cart');
        $this->assertFlashMessage('Please choose a quantity of at least 1.');
        $this->assertSame(0, $this->fetchTable('CartItems')->find()->count());
    }
}
