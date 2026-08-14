<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Authentication\Identity;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Admin\ContactMessagesController Test Case
 *
 * @link \App\Controller\Admin\ContactMessagesController
 */
class ContactMessagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.ContactMessages',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
    }

    /**
     * Log in the fixture administrator via the session.
     *
     * @return void
     */
    protected function loginAsAdmin(): void
    {
        $this->session([
            'Auth' => new Identity(
                $this->fetchTable('Users')->get(1),
            ),
        ]);
    }

    /**
     * Test the message list requires login.
     *
     * @return void
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->get('/admin/contact-messages');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/login');
    }

    /**
     * Test the message list shows fixture data for an authenticated user.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/contact-messages');

        $this->assertResponseOk();
        $this->assertResponseContains('Smart bulb enquiry');
        $this->assertResponseContains('Installation quote');
    }

    /**
     * Test the unread tally that the heading and the nav badge share.
     *
     * Both read the single `$unreadCount` that AppController::beforeRender sets;
     * this controller used to run the identical COUNT a second time for its own
     * heading. Asserting on both rendered places, and on the view variable
     * itself, means dropping that duplicate cannot silently blank either one —
     * and the second half checks the number is still live rather than cached,
     * by reading the only unread message and expecting the tally to clear.
     *
     * @return void
     */
    public function testIndexShowsUnreadCountFromSharedViewVariable(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/contact-messages');

        $this->assertResponseOk();
        $this->assertSame(1, $this->viewVariable('unreadCount'));
        $this->assertResponseContains('1 unread');
        $this->assertResponseContains('<span class="badge-count">1</span>');

        $this->get('/admin/contact-messages/view/1');
        $this->get('/admin/contact-messages');

        $this->assertResponseOk();
        $this->assertSame(0, $this->viewVariable('unreadCount'));
        $this->assertResponseNotContains('1 unread');
        $this->assertResponseNotContains('badge-count');
    }

    /**
     * Test viewing a message marks it as read.
     *
     * @return void
     */
    public function testViewMarksMessageAsRead(): void
    {
        $this->loginAsAdmin();

        $table = $this->fetchTable('ContactMessages');
        $this->assertFalse((bool)$table->get(1)->is_read);

        $this->get('/admin/contact-messages/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Do your smart bulbs work with Google Home?');
        $this->assertTrue((bool)$table->get(1)->is_read);
    }

    /**
     * Test that malformed or missing ids are 404s rather than 500s.
     *
     * @return void
     */
    public function testViewWithInvalidIdReturnsNotFound(): void
    {
        $this->loginAsAdmin();

        foreach (['/admin/contact-messages/view/abc', '/admin/contact-messages/view'] as $url) {
            $this->get($url);
            $this->assertResponseCode(404, $url . ' should not raise a server error');
        }
    }

    /**
     * Test deleting a message removes it.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->loginAsAdmin();

        $this->post('/admin/contact-messages/delete/1');

        $this->assertResponseCode(302);

        $table = $this->fetchTable('ContactMessages');
        $this->assertFalse($table->exists(['id' => 1]));
    }

    /**
     * Test that delete is not allowed via GET.
     *
     * @return void
     */
    public function testDeleteRejectsGet(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/contact-messages/delete/1');

        $this->assertResponseCode(405);

        $table = $this->fetchTable('ContactMessages');
        $this->assertTrue($table->exists(['id' => 1]));
    }
}
