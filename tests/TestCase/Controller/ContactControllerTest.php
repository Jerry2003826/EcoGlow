<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ContactController Test Case
 *
 * @link \App\Controller\ContactController
 */
class ContactControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.ContactMessages',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Never hit the real reCAPTCHA API during tests.
        Configure::write('Recaptcha.enabled', false);

        $this->enableCsrfToken();
        $this->enableRetainFlashMessages();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Configure::delete('Recaptcha.enabled');
        Configure::delete('Recaptcha.secret');

        parent::tearDown();
    }

    /**
     * Test that the contact form page loads.
     *
     * @return void
     */
    public function testIndexGet(): void
    {
        $this->get('/contact');

        $this->assertResponseOk();
        $this->assertResponseContains('Contact Eco Glow Lighting');
    }

    /**
     * Test a valid contact form submission is saved.
     *
     * @return void
     */
    public function testIndexPostSuccess(): void
    {
        $data = [
            'name' => 'New Visitor',
            'email' => 'visitor@example.com',
            'phone' => '0400000002',
            'subject' => 'Floor lamp question',
            'message' => 'Do you ship floor lamps to Perth?',
        ];
        $this->post('/contact', $data);

        $this->assertResponseCode(302);

        $messages = $this->fetchTable('ContactMessages')
            ->find()
            ->where(['email' => 'visitor@example.com']);
        $this->assertSame(1, $messages->count());
        $this->assertFalse($messages->first()->is_read);
    }

    /**
     * Test that a submitter cannot mass assign admin-owned columns.
     *
     * Forging `is_read` would hide the message from the unread badge, and
     * forging `created` would bury it at the bottom of the admin list.
     *
     * @return void
     */
    public function testIndexPostIgnoresForgedAdminFields(): void
    {
        $data = [
            'name' => 'Sneaky Visitor',
            'email' => 'sneaky@example.com',
            'subject' => 'Hidden message',
            'message' => 'This should still show up as unread.',
            'is_read' => 1,
            'created' => '1990-01-01 00:00:00',
        ];
        $this->post('/contact', $data);

        $this->assertResponseCode(302);

        $message = $this->fetchTable('ContactMessages')
            ->find()
            ->where(['email' => 'sneaky@example.com'])
            ->firstOrFail();

        $this->assertFalse((bool)$message->is_read);
        $this->assertNotSame('1990-01-01', $message->created->format('Y-m-d'));
    }

    /**
     * Test that invalid data is rejected with a flash error.
     *
     * @return void
     */
    public function testIndexPostValidationFailure(): void
    {
        $data = [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => '',
        ];
        $this->post('/contact', $data);

        $this->assertResponseOk();
        $this->assertResponseContains('Your message could not be sent');

        $count = $this->fetchTable('ContactMessages')->find()->count();
        $this->assertSame(2, $count);
    }

    /**
     * Test that over-length email/message are rejected by validation.
     *
     * Without a maxLength rule these overflow the VARCHAR(255) / TEXT columns
     * and raise an uncaught database error (HTTP 500) on MySQL. Validation
     * must catch them first so the failure is a friendly form error instead.
     *
     * @return void
     */
    public function testIndexPostRejectsOverlongFields(): void
    {
        $cases = [
            'email' => [
                'name' => 'Len',
                'email' => str_repeat('a', 244) . '@example.com', // 256 chars
                'subject' => 'Long email',
                'message' => 'hi',
            ],
            'message' => [
                'name' => 'Len',
                'email' => 'len@example.com',
                'subject' => 'Long message',
                'message' => str_repeat('C', 65536), // one over the TEXT limit
            ],
        ];

        foreach ($cases as $field => $data) {
            $this->post('/contact', $data);

            $this->assertResponseOk($field . ' should be rejected, not saved');
            $this->assertResponseContains('Your message could not be sent');
        }

        // Only the two fixture rows remain; nothing over-length was persisted.
        $count = $this->fetchTable('ContactMessages')->find()->count();
        $this->assertSame(2, $count);
    }

    /**
     * Test that a failed CAPTCHA prevents the message from being saved.
     *
     * @return void
     */
    public function testIndexPostCaptchaFailure(): void
    {
        // An empty secret makes the verifier reject the submission without any
        // network call, so this exercises the captcha-failure branch offline.
        Configure::write('Recaptcha.enabled', true);
        Configure::write('Recaptcha.secret', '');

        $data = [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'subject' => 'Spam',
            'message' => 'Buy stuff now',
            'g-recaptcha-response' => 'fake-token',
        ];
        $this->post('/contact', $data);

        $this->assertResponseOk();
        $this->assertResponseContains('Please complete the CAPTCHA');

        $messages = $this->fetchTable('ContactMessages')
            ->find()
            ->where(['email' => 'bot@example.com']);
        $this->assertSame(0, $messages->count());
    }
}
