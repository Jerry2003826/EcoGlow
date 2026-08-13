<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Product detail add-to-basket availability.
 */
class ShopControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.SiteSettings',
        'app.ContactMessages',
    ];

    /**
     * In-stock variants keep Add to basket enabled.
     *
     * @return void
     */
    public function testProductAddButtonEnabledWhenInStock(): void
    {
        $this->get('/shop/product/marlow-floor-lamp');
        $this->assertResponseOk();
        $this->assertResponseContains('Add to basket');
        $this->assertResponseNotContains('This item is temporarily out of stock.');
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*disabled[^>]*>\s*Add to basket/s',
            (string)$this->_response->getBody(),
        );
    }

    /**
     * Out of stock disables the button at render time and names the reason.
     *
     * @return void
     */
    public function testProductAddButtonDisabledWhenOutOfStock(): void
    {
        $this->fetchTable('InventoryBalances')->getConnection()->execute(
            'UPDATE inventory_balances SET quantity_on_hand = 0 WHERE product_variant_id = 1',
        );

        $this->get('/shop/product/marlow-floor-lamp');
        $this->assertResponseOk();
        $this->assertResponseContains('This item is temporarily out of stock.');
        $this->assertResponseContains('aria-describedby="basket-oos"');
        $this->assertMatchesRegularExpression(
            '/<button[^>]*disabled[^>]*>\s*Add to basket/s',
            (string)$this->_response->getBody(),
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]*aria-disabled="true"[^>]*>\s*Add to basket/s',
            (string)$this->_response->getBody(),
        );
    }
}
