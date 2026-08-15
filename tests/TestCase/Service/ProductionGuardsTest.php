<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Security\ProductionGuards;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Engine\NullEngine;
use Cake\Cache\Engine\RedisEngine;
use Cake\Core\Configure;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use Redis;
use ReflectionClass;
use RuntimeException;
use Throwable;

/**
 * Production bootstrap guards read registered engines, not consumed Configure.
 */
class ProductionGuardsTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $originalThrottle = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $originalMail = null;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $probed = (new ReflectionClass(ProductionGuards::class))->getProperty('probed');
        $probed->setValue(null, false);
        $throttle = Cache::getConfig('login_throttle');
        $this->originalThrottle = is_array($throttle) ? $throttle : null;
        $mail = TransportFactory::getConfig('default');
        $this->originalMail = is_array($mail) ? $mail : null;
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        putenv('ECOGLOW_TEST_PRODUCTION_GUARDS');
        Configure::write('debug', true);
        Cache::drop('login_throttle');
        if ($this->originalThrottle !== null) {
            Cache::setConfig('login_throttle', $this->originalThrottle);
        }
        TransportFactory::drop('default');
        if ($this->originalMail !== null) {
            TransportFactory::setConfig('default', $this->originalMail);
        }
        parent::tearDown();
    }

    /**
     * File cache is not an acceptable production rate-limit store.
     *
     * @return void
     */
    public function testFileCacheIsRejected(): void
    {
        $this->useFileThrottle();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Redis');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * Cake fallback to NullEngine is forbidden in production.
     *
     * @return void
     */
    public function testFallbackMustBeDisabled(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => true,
            'host' => '127.0.0.1',
            'port' => 6379,
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_fallback_',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('fallback=false');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * NullEngine looks successful to counters but stores nothing.
     *
     * @return void
     */
    public function testNullEngineIsRejected(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => NullEngine::class,
            'fallback' => false,
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_null_',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('working Redis');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * Public Redis without TLS or a password is rejected before connect.
     *
     * @return void
     */
    public function testRemoteRedisWithoutAuthIsRejected(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => false,
            'url' => 'redis://redis.example.com:6379/0',
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_public_',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('rediss://');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * A password is not a substitute for TLS on a public Redis host.
     *
     * @return void
     */
    public function testPublicRedisPasswordWithoutTlsIsRejected(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => false,
            'url' => 'redis://:secret@redis.example.com:6379/0',
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_public_pw_',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('rediss://');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * A Redis URL that cannot be reached must fail closed.
     *
     * @return void
     */
    public function testUnreachableRedisIsRejected(): void
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is not installed.');
        }
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => false,
            'host' => '127.0.0.1',
            'port' => 1,
            'timeout' => 0.2,
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_bad_',
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('working Redis');
        ProductionGuards::assertRateLimitStore();
    }

    /**
     * A reachable Redis engine is accepted after a write/increment/read probe.
     *
     * @return void
     */
    public function testWorkingRedisIsAccepted(): void
    {
        if (!$this->redisIsReachable()) {
            $this->markTestSkipped('Redis is not reachable on 127.0.0.1:6379.');
        }
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => RedisEngine::class,
            'fallback' => false,
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 1,
            'duration' => '+1 minute',
            'prefix' => 'test_throttle_ok_',
        ]);
        ProductionGuards::assertRateLimitStore();
        $this->assertInstanceOf(RedisEngine::class, Cache::pool('login_throttle'));
    }

    /**
     * Debug mail is not an acceptable production transport.
     *
     * @return void
     */
    public function testDebugMailIsRejected(): void
    {
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => DebugTransport::class]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Debug email transport');
        ProductionGuards::assertEmailTransport();
    }

    /**
     * Remote SMTP without TLS is rejected.
     *
     * @return void
     */
    public function testRemoteSmtpWithoutTlsIsRejected(): void
    {
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', [
            'className' => SmtpTransport::class,
            'host' => 'smtp.example.com',
            'tls' => false,
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TLS');
        ProductionGuards::assertEmailTransport();
    }

    /**
     * A child process boots the real Application after Configure::consume().
     *
     * @return void
     */
    public function testProductionBootstrapScenarios(): void
    {
        $redis = $this->bootProduction([
            'EMAIL_TRANSPORT' => 'Smtp',
            'CACHE_LOGIN_THROTTLE_URL' => 'redis://127.0.0.1:6379/0',
        ]);
        if ($this->redisIsReachable()) {
            $this->assertSame(0, $redis['code'], $redis['output']);
            $this->assertStringContainsString('"ok":true', $redis['output']);
        } else {
            $this->assertNotSame(0, $redis['code'], $redis['output']);
        }

        $file = $this->bootProduction([
            'EMAIL_TRANSPORT' => 'Smtp',
            'CACHE_LOGIN_THROTTLE_URL' => '',
        ]);
        $this->assertNotSame(0, $file['code'], $file['output']);
        $this->assertStringContainsString('Redis', $file['output']);

        $debugMail = $this->bootProduction([
            'EMAIL_TRANSPORT' => 'Debug',
            'CACHE_LOGIN_THROTTLE_URL' => 'redis://127.0.0.1:6379/0',
        ]);
        $this->assertNotSame(0, $debugMail['code'], $debugMail['output']);
        $this->assertStringContainsString('Debug email transport', $debugMail['output']);
    }

    /**
     * @return void
     */
    private function useFileThrottle(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => FileEngine::class,
            'fallback' => false,
            'prefix' => 'test_throttle_file_',
            'path' => CACHE,
            'duration' => '+1 minute',
        ]);
    }

    /**
     * @return bool
     */
    private function redisIsReachable(): bool
    {
        if (!extension_loaded('redis')) {
            return false;
        }
        try {
            $redis = new Redis();
            if (!$redis->connect('127.0.0.1', 6379, 0.2)) {
                return false;
            }
            $redis->close();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, string> $extraEnv Extra environment variables.
     * @return array{code: int, output: string}
     */
    private function bootProduction(array $extraEnv): array
    {
        $script = dirname(__DIR__, 2) . '/bin/production_guards.php';
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }
        $env['DEBUG'] = '0';
        $env['ECOGLOW_TEST_PRODUCTION_GUARDS'] = '1';
        if (($env['SECURITY_SALT'] ?? '') === '') {
            $env['SECURITY_SALT'] = str_repeat('a', 64);
        }
        foreach ($extraEnv as $key => $value) {
            $env[$key] = $value;
        }
        $process = proc_open(
            [PHP_BINARY, $script],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            dirname(__DIR__, 3),
            $env,
        );
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'code' => proc_close($process),
            'output' => $stdout . $stderr,
        ];
    }
}
