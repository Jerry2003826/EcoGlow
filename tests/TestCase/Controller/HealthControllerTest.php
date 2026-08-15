<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Middleware\AbuseThrottleMiddleware;
use App\Service\Security\HealthGate;
use App\Service\Security\RateLimitService;
use Cake\Cache\Cache;
use Cake\Cache\Engine\RedisEngine;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Valid readiness probes must never increment the abuse counter.
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
     * Authenticated probes stay healthy well past the anonymous threshold.
     *
     * @return void
     */
    public function testValidTokenIsNeverRateLimited(): void
    {
        putenv('HEALTH_READY_TOKEN=health-secret');
        for ($i = 0; $i < 120; $i++) {
            $this->configRequest([
                'headers' => ['X-Health-Token' => 'health-secret'],
            ]);
            $this->get('/health/ready');
            $this->assertResponseOk();
        }
    }

    /**
     * Wrong tokens are counted and eventually locked out.
     *
     * @return void
     */
    public function testWrongTokenIsRateLimited(): void
    {
        putenv('HEALTH_READY_TOKEN=health-secret');
        for ($i = 0; $i < AbuseThrottleMiddleware::MAX_HEALTH; $i++) {
            $this->configRequest([
                'headers' => ['X-Health-Token' => 'wrong-token'],
            ]);
            $this->get('/health/ready');
            $this->assertResponseCode(401);
        }
        $this->configRequest([
            'headers' => ['X-Health-Token' => 'wrong-token'],
        ]);
        $this->get('/health/ready');
        $this->assertResponseCode(429);
        $this->assertHeaderContains('Cache-Control', 'no-store');
    }

    /**
     * Production without a dedicated token is a misconfiguration, not 200.
     *
     * @return void
     */
    public function testProductionWithoutTokenIsDenied(): void
    {
        $this->assertSame(503, HealthGate::denyStatus('', '', true, '127.0.0.1'));
    }

    /**
     * A down database must fail readiness.
     *
     * @return void
     */
    public function testDatabaseFailureIs503(): void
    {
        putenv('HEALTH_READY_TOKEN=health-secret');
        ConnectionManager::dropAlias('default');
        ConnectionManager::drop('default');
        ConnectionManager::setConfig('default', [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Mysql',
            'host' => '127.0.0.1',
            'port' => 1,
            'username' => 'nobody',
            'password' => 'nobody',
            'database' => 'missing',
            'timeout' => 1,
        ]);
        try {
            $this->configRequest([
                'headers' => ['X-Health-Token' => 'health-secret'],
            ]);
            $this->get('/health/ready');
            $this->assertResponseCode(503);
        } finally {
            ConnectionManager::drop('default');
            ConnectionHelper::addTestAliases();
        }
    }

    /**
     * A configured Redis engine that cannot be reached fails readiness.
     *
     * @return void
     */
    public function testRedisFailureIs503(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is not installed.');
        }
        putenv('HEALTH_READY_TOKEN=health-secret');
        $original = Cache::getConfig('login_throttle');
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => false,
            'host' => '127.0.0.1',
            'port' => 1,
            'timeout' => 0.2,
            'duration' => '+1 minute',
            'prefix' => 'test_health_bad_',
        ]);
        try {
            $this->configRequest([
                'headers' => ['X-Health-Token' => 'health-secret'],
            ]);
            $this->get('/health/ready');
            $this->assertResponseCode(503);
        } finally {
            Cache::drop('login_throttle');
            if (is_array($original)) {
                Cache::setConfig('login_throttle', $original);
            }
        }
    }
}
