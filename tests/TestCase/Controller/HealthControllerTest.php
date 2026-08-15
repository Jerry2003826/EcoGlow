<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Middleware\AbuseThrottleMiddleware;
use App\Service\Security\ProductionGuards;
use App\Service\Security\RateLimitService;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use ReflectionClass;

/**
 * Readiness must stay cheap, uncached, and unusable as a public Redis hammer.
 */
class HealthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        putenv('HEALTH_READY_TOKEN');
        putenv('ECOGLOW_TEST_PRODUCTION_GUARDS');
        Configure::write('debug', true);
        $this->resetReadyProbe();
        RateLimitService::clear(AbuseThrottleMiddleware::SCOPE_HEALTH, '127.0.0.1');
        Cache::clear('login_throttle');
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        putenv('HEALTH_READY_TOKEN');
        putenv('ECOGLOW_TEST_PRODUCTION_GUARDS');
        Configure::write('debug', true);
        $this->resetReadyProbe();
        RateLimitService::clear(AbuseThrottleMiddleware::SCOPE_HEALTH, '127.0.0.1');
        parent::tearDown();
    }

    /**
     * Debug readiness is anonymous and must not be cached.
     *
     * @return void
     */
    public function testReadyIsOkAndUncached(): void
    {
        $this->get('/health/ready');
        $this->assertResponseOk();
        $this->assertResponseContains('{"ok":true}');
        $this->assertHeaderContains('Cache-Control', 'no-store');
    }

    /**
     * A configured token is required even in debug.
     *
     * @return void
     */
    public function testReadyRejectsMissingToken(): void
    {
        putenv('HEALTH_READY_TOKEN=health-secret');
        $this->get('/health/ready');
        $this->assertResponseCode(401);
        $this->assertResponseContains('{"ok":false}');
    }

    /**
     * The configured token is accepted via X-Health-Token.
     *
     * @return void
     */
    public function testReadyAcceptsMatchingToken(): void
    {
        putenv('HEALTH_READY_TOKEN=health-secret');
        $this->configRequest([
            'headers' => ['X-Health-Token' => 'health-secret'],
        ]);
        $this->get('/health/ready');
        $this->assertResponseOk();
        $this->assertResponseContains('{"ok":true}');
    }

    /**
     * Repeated anonymous probes are rate-limited before Redis work.
     *
     * @return void
     */
    public function testReadyIsRateLimited(): void
    {
        for ($i = 0; $i < AbuseThrottleMiddleware::MAX_HEALTH; $i++) {
            $this->get('/health/ready');
            $this->assertResponseOk();
        }
        $this->get('/health/ready');
        $this->assertResponseCode(429);
        $this->assertHeaderContains('Cache-Control', 'no-store');
    }

    /**
     * @return void
     */
    private function resetReadyProbe(): void
    {
        $probed = (new ReflectionClass(ProductionGuards::class))->getProperty('probed');
        $probed->setValue(null, false);
    }
}
