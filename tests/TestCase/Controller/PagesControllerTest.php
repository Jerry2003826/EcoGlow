<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\Constraint\Response\StatusCode;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * PagesControllerTest class
 */
class PagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * testDisplay method
     *
     * @return void
     */
    public function testDisplay()
    {
        Configure::write('debug', true);
        $this->get('/');
        $this->assertResponseOk();
        $this->assertResponseContains('Eco Glow Lighting');
        $this->assertResponseContains('<html lang="en">');
    }

    /**
     * Test the four storefront pages render for an anonymous visitor.
     *
     * They are connected explicitly in config/routes.php rather than reached
     * through /pages/*, so this covers the routes as much as the templates: a
     * typo in either would show up as a 404 here. Each assertion looks for
     * something only that page renders, so a route pointing at the wrong
     * template still fails.
     *
     * @return void
     */
    public function testStorefrontPagesRenderPublicly()
    {
        Configure::write('debug', true);

        $pages = [
            '/shop' => 'All lighting',
            '/shop/product' => 'Marlow Floor Lamp',
            '/cart' => 'Your basket',
            '/register' => 'Create your account',
        ];

        foreach ($pages as $url => $heading) {
            $this->get($url);

            $this->assertResponseOk($url . ' should render without a login');
            $this->assertResponseContains($heading, $url . ' should render its own page heading');
        }
    }

    /**
     * Test the header points at the storefront routes but keeps search off.
     *
     * The basket icon and Shop were placeholders until these routes existed.
     * Search has no index behind it and stays disabled, which is the part worth
     * pinning down — it is the one that would be easy to "finish" by accident.
     *
     * @return void
     */
    public function testHeaderLinksToStorefrontAndLeavesSearchDisabled()
    {
        Configure::write('debug', true);

        $this->get('/');

        $this->assertResponseOk();
        $this->assertResponseContains('href="/shop"');
        $this->assertResponseContains('href="/cart"');
        $this->assertResponseContains('aria-label="Basket"');
        $this->assertResponseContains('disabled aria-label="Search (coming soon)"');
    }

    /**
     * Test that missing template renders 404 page in production
     *
     * @return void
     */
    public function testMissingTemplate()
    {
        Configure::write('debug', false);
        // Turning debug off is also what mounts HttpsEnforcerMiddleware, so the
        // request has to arrive over HTTPS like it would in production.
        // Otherwise the enforcer answers 301 and the missing-template handling
        // under test never runs.
        $this->configRequest(['environment' => ['HTTPS' => 'on']]);
        $this->get('/pages/not_existing');

        $this->assertResponseCode(404);
    }

    /**
     * Test that missing template in debug mode renders missing_template error page
     *
     * @return void
     */
    public function testMissingTemplateInDebug()
    {
        Configure::write('debug', true);
        $this->get('/pages/not_existing');

        $this->assertResponseCode(404);
    }

    /**
     * Test directory traversal protection
     *
     * @return void
     */
    public function testDirectoryTraversalProtection()
    {
        $this->get('/pages/../Layout/ajax');
        $this->assertResponseCode(404);
    }

    /**
     * Test that CSRF protection is applied to page rendering.
     *
     * @return void
     */
    public function testCsrfAppliedError()
    {
        $this->post('/', ['hello' => 'world']);

        $this->assertResponseCode(403);
        $this->assertResponseContains('CSRF');
    }

    /**
     * Test that CSRF protection is applied to page rendering.
     *
     * @return void
     */
    public function testCsrfAppliedOk()
    {
        $this->enableCsrfToken();
        $this->post('/', ['hello' => 'world']);

        $this->assertThat(403, $this->logicalNot(new StatusCode($this->_response)));
        $this->assertResponseNotContains('CSRF');
    }
}
