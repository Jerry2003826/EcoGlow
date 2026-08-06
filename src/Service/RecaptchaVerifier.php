<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Throwable;

/**
 * Verifies Google reCAPTCHA v2 responses against the siteverify endpoint.
 *
 * Configuration (in config/app_local.php):
 *   'Recaptcha' => ['sitekey' => '...', 'secret' => '...', 'enabled' => true]
 *
 * During development Google's official test keys always pass verification.
 * In the test environment the verifier is disabled so no network calls happen.
 */
class RecaptchaVerifier
{
    /**
     * reCAPTCHA verification endpoint.
     */
    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * Google's universal *test* secret. It accepts any token and must never be
     * used in production, so we refuse it there to fail closed rather than
     * silently letting every submission through.
     */
    protected const TEST_SECRET = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    /**
     * HTTP client used to reach the siteverify endpoint.
     *
     * @var \Cake\Http\Client
     */
    protected Client $client;

    /**
     * @param \Cake\Http\Client|null $client Optional client override (injected in tests).
     */
    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client(['timeout' => 10]);
    }

    /**
     * Verify a reCAPTCHA response token.
     *
     * @param string|null $responseToken The `g-recaptcha-response` token from the form.
     * @param string|null $remoteIp The submitter's IP address (optional).
     * @return bool True when the token is accepted by Google.
     */
    public function verify(?string $responseToken, ?string $remoteIp = null): bool
    {
        $enabled = (bool)Configure::read('Recaptcha.enabled', true);
        if (!$enabled) {
            return true;
        }

        $secret = (string)Configure::read('Recaptcha.secret');
        if ($secret === '' || empty($responseToken)) {
            return false;
        }

        // Refuse Google's test secret outside of local development: it would
        // accept any token and turn the CAPTCHA into a no-op.
        if ($secret === self::TEST_SECRET && !Configure::read('debug')) {
            Log::warning(
                'reCAPTCHA is using Google\'s test secret in a non-debug environment; ' .
                'set a real RECAPTCHA_SECRET. Rejecting submission to fail closed.',
            );

            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $responseToken,
        ];
        if ($remoteIp !== null) {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            $response = $this->client->post(self::VERIFY_URL, $payload);
            $result = $response->getJson();
        } catch (Throwable $exception) {
            // Network/API failures fail closed: treat as an invalid captcha.
            return false;
        }

        return is_array($result) && ($result['success'] ?? false) === true;
    }
}
