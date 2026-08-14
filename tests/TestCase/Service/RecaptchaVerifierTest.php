<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\RecaptchaVerifier;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Service\RecaptchaVerifier Test Case
 *
 * @link \App\Service\RecaptchaVerifier
 */
class RecaptchaVerifierTest extends TestCase
{
    use HttpClientTrait;

    /**
     * Google's universal test secret (accepts any token).
     *
     * @var string
     */
    private const TEST_SECRET = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Recaptcha.enabled', true);
        Configure::write('Recaptcha.secret', 'a-real-looking-secret');
        Configure::write('debug', true);
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
     * When verification is disabled every token passes without a network call.
     *
     * @return void
     */
    public function testDisabledAlwaysPasses(): void
    {
        Configure::write('Recaptcha.enabled', false);

        $this->assertTrue((new RecaptchaVerifier())->verify(null));
        $this->assertTrue((new RecaptchaVerifier())->verify(''));
    }

    /**
     * An empty secret fails closed without a network call.
     *
     * @return void
     */
    public function testEmptySecretFailsClosed(): void
    {
        Configure::write('Recaptcha.secret', '');

        $this->assertFalse((new RecaptchaVerifier())->verify('some-token'));
    }

    /**
     * An empty token fails closed without a network call.
     *
     * @return void
     */
    public function testEmptyTokenFailsClosed(): void
    {
        $this->assertFalse((new RecaptchaVerifier())->verify(''));
        $this->assertFalse((new RecaptchaVerifier())->verify(null));
    }

    /**
     * Google's test secret is refused when debug is off (production).
     *
     * @return void
     */
    public function testTestSecretRefusedInProduction(): void
    {
        Configure::write('debug', false);
        Configure::write('Recaptcha.secret', self::TEST_SECRET);

        $this->assertFalse((new RecaptchaVerifier())->verify('any-token'));
    }

    /**
     * A successful siteverify response is accepted.
     *
     * @return void
     */
    public function testSuccessfulVerification(): void
    {
        $this->mockClientPost(
            RecaptchaVerifier::VERIFY_URL,
            $this->newClientResponse(
                200,
                ['Content-Type: application/json'],
                (string)json_encode(['success' => true]),
            ),
        );

        $this->assertTrue((new RecaptchaVerifier())->verify('valid-token', '203.0.113.5'));
    }

    /**
     * A rejection from Google returns false.
     *
     * @return void
     */
    public function testGoogleRejectionReturnsFalse(): void
    {
        $this->mockClientPost(
            RecaptchaVerifier::VERIFY_URL,
            $this->newClientResponse(
                200,
                ['Content-Type: application/json'],
                (string)json_encode(['success' => false, 'error-codes' => ['invalid-input-response']]),
            ),
        );

        $this->assertFalse((new RecaptchaVerifier())->verify('bad-token'));
    }

    /**
     * A malformed (non-JSON) response fails closed.
     *
     * @return void
     */
    public function testMalformedResponseFailsClosed(): void
    {
        $this->mockClientPost(
            RecaptchaVerifier::VERIFY_URL,
            $this->newClientResponse(200, ['Content-Type: text/html'], 'not-json'),
        );

        $this->assertFalse((new RecaptchaVerifier())->verify('some-token'));
    }
}
