<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Middleware\AbuseThrottleMiddleware;
use App\Service\Security\RateLimitService;
use Cake\Cache\Cache;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Anonymous CSP reports must be size-limited and rate-limited.
 */
class CspReportsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('login_throttle');
        RateLimitService::clear(AbuseThrottleMiddleware::SCOPE_CSP, '127.0.0.1');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        RateLimitService::clear(AbuseThrottleMiddleware::SCOPE_CSP, '127.0.0.1');
        Cache::clear('login_throttle');
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testAcceptsJsonReport(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/csp-report'],
        ]);
        $this->post('/csp-report', '{"csp-report":{"effective-directive":"script-src"}}');
        $this->assertResponseCode(204);
    }

    /**
     * @return void
     */
    public function testRejectsWrongContentType(): void
    {
        $this->configRequest([
            'headers' => ['Content-Type' => 'text/plain'],
        ]);
        $this->post('/csp-report', 'not-json');
        $this->assertResponseCode(415);
    }

    /**
     * @return void
     */
    public function testRejectsOversizedBody(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/csp-report',
                'Content-Length' => '70000',
            ],
        ]);
        $this->post('/csp-report', '{"csp-report":{}}');
        $this->assertResponseCode(413);
    }

    /**
     * @return void
     */
    public function testRateLimitsRepeatedReports(): void
    {
        for ($i = 0; $i < AbuseThrottleMiddleware::MAX_CSP; $i++) {
            $this->configRequest([
                'headers' => ['Content-Type' => 'application/csp-report'],
            ]);
            $this->post('/csp-report', '{"csp-report":{}}');
            $this->assertResponseCode(204);
        }
        $this->configRequest([
            'headers' => ['Content-Type' => 'application/csp-report'],
        ]);
        $this->post('/csp-report', '{"csp-report":{}}');
        $this->assertResponseCode(429);
    }
}
