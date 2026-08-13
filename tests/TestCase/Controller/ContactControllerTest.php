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
     * The controls TC3 requires, as posted field name => [tag, label text].
     *
     * Held as data so a field deleted from the template fails with that field's
     * own name in the failure message.
     *
     * @var array<string, array{string, string}>
     */
    private const REQUIRED_CONTROLS = [
        'name' => ['input', 'Your Name'],
        'email' => ['input', 'Email'],
        'subject' => ['input', 'Subject'],
        'message' => ['textarea', 'Message'],
    ];

    /**
     * The per-field message expected when the form is submitted empty, with a
     * malformed address in the email field.
     *
     * @var array<string, string>
     */
    private const FIELD_ERRORS = [
        'name' => 'This field cannot be left empty',
        'email' => 'The provided value must be an e-mail address',
        'subject' => 'This field cannot be left empty',
        'message' => 'This field cannot be left empty',
    ];

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.ContactMessages',
    ];

    /**
     * The reCAPTCHA settings as they were before this test class touched them.
     *
     * @var array<string, mixed>
     */
    private array $recaptchaConfig = [];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->recaptchaConfig = (array)Configure::read('Recaptcha');

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
        // Put the whole subtree back rather than deleting the keys: the CAPTCHA
        // rendering test overrides the site key too, and a deleted key is not
        // the same state as the configured one for whatever runs next.
        Configure::write('Recaptcha', $this->recaptchaConfig);

        parent::tearDown();
    }

    /**
     * Test that the contact form page loads with all of its fields.
     *
     * TC3 asks for the fields themselves, not just a page that renders, so each
     * control is asserted by name along with the label bound to it. Without
     * these a field could be dropped from the template unnoticed.
     *
     * @return void
     */
    public function testIndexGet(): void
    {
        $this->get('/contact');

        $this->assertResponseOk();
        $this->assertResponseContains('Contact Eco Glow Lighting');

        foreach (self::REQUIRED_CONTROLS as $field => [$tag, $label]) {
            $this->assertResponseRegExp(
                '~<' . $tag . '[^>]+name="' . $field . '"~',
                sprintf('The contact form is missing its %s control.', $label),
            );
            $this->assertResponseContains(
                sprintf('<label for="%s">%s</label>', $field, $label),
                sprintf('The %s control has no label bound to it.', $label),
            );
        }
    }

    /**
     * Test that the CAPTCHA widget renders when reCAPTCHA is switched on.
     *
     * setUp() disables reCAPTCHA for the rest of the suite so no test calls
     * Google, which also means the widget never appears there. This case turns
     * it back on with a stand-in site key — rendering the container is pure
     * markup and needs no network — so TC6 has something asserting it.
     *
     * @return void
     */
    public function testIndexGetRendersCaptchaWhenEnabled(): void
    {
        Configure::write('Recaptcha.enabled', true);
        Configure::write('Recaptcha.sitekey', 'test-site-key');

        $this->get('/contact');

        $this->assertResponseOk();
        $this->assertResponseRegExp(
            '~class="g-recaptcha[^"]*"[^>]*data-sitekey="test-site-key"~',
            'The reCAPTCHA widget container is missing from the form.',
        );
        $this->assertResponseContains(
            'https://www.google.com/recaptcha/api.js',
            'The widget container renders but Google\'s script that fills it does not load.',
        );

        // A configured site key must reach the real widget, not the
        // misconfiguration notice that stands in for it.
        $this->assertResponseNotContains('reCAPTCHA is enabled but no site key is configured');
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
        $this->assertFlashMessage('Thank you! Your message has been sent. We will get back to you soon.');

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
     * Test that every field in error carries its own message.
     *
     * The page-level banner is already covered above; what TC5 asks for is
     * guidance beside each offending field, tied to that field so a screen
     * reader reads it out with the control rather than as loose text.
     *
     * @return void
     */
    public function testIndexPostShowsPerFieldValidationErrors(): void
    {
        $this->post('/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'subject' => '',
            'message' => '',
        ]);

        $this->assertResponseOk();

        foreach (self::FIELD_ERRORS as $field => $message) {
            $this->assertResponseRegExp(
                '~id="' . $field . '-error"[^>]*>\s*' . preg_quote($message, '~') . '~',
                sprintf('The %s field has no error message of its own.', $field),
            );
            $this->assertResponseRegExp(
                '~name="' . $field . '"[^>]+aria-describedby="' . $field . '-error"~',
                sprintf('The %s field is not wired to its own error message.', $field),
            );
        }

        // Phone was left blank too, but it is optional — flagging it would send
        // the user hunting for a problem that is not there.
        $this->assertResponseNotContains('id="phone-error"');
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
