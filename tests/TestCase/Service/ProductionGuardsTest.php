<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Security\ProductionGuards;
use Cake\Cache\Cache;
use Cake\Cache\Engine\FileEngine;
use Cake\Core\Configure;
use Cake\Mailer\Transport\DebugTransport;
use Cake\Mailer\Transport\SmtpTransport;
use Cake\Mailer\TransportFactory;
use Cake\TestSuite\TestCase;
use RuntimeException;

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
     * SMTP plus a Redis URL is accepted without instantiating Redis.
     *
     * @return void
     */
    public function testRedisUrlIsRecognised(): void
    {
        Cache::drop('login_throttle');
        Cache::setConfig('login_throttle', [
            'className' => FileEngine::class,
            'prefix' => 'test_throttle_redis_',
            'path' => CACHE,
            'duration' => '+1 minute',
            'url' => 'redis://127.0.0.1:6379/0',
        ]);
        $config = Cache::getConfig('login_throttle');
        $this->assertIsArray($config);
        $this->assertTrue(ProductionGuards::isRedisStore($config));
        ProductionGuards::assertRateLimitStore();
        TransportFactory::drop('default');
        TransportFactory::setConfig('default', ['className' => SmtpTransport::class]);
        ProductionGuards::assertEmailTransport();
    }

    /**
     * A child process boots the real Application after Configure::consume().
     *
     * @return void
     */
    public function testProductionBootstrapScenarios(): void
    {
        $ok = $this->bootProduction([
            'EMAIL_TRANSPORT' => 'Smtp',
            'CACHE_LOGIN_THROTTLE_URL' => 'redis://127.0.0.1:6379/0',
        ]);
        $this->assertSame(0, $ok['code'], $ok['output']);
        $this->assertStringContainsString('"ok":true', $ok['output']);
        $this->assertStringContainsString('"redis":true', $ok['output']);

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
            'prefix' => 'test_throttle_file_',
            'path' => CACHE,
            'duration' => '+1 minute',
        ]);
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
