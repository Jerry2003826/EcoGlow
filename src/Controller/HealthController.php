<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Security\HealthGate;
use App\Service\Security\ProductionGuards;
use Cake\Cache\Cache;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Response;
use RuntimeException;
use Throwable;
use function Cake\Core\env;

/**
 * Deployment readiness. CSRF is skipped for this path only.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 */
class HealthController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['ready']);
        $this->viewBuilder()->disableAutoLayout();
    }

    /**
     * @return \Cake\Http\Response
     */
    public function ready(): Response
    {
        $this->request->allowMethod(['get']);
        $denied = HealthGate::denyStatus(
            (string)env('HEALTH_READY_TOKEN', ''),
            (string)$this->request->getHeaderLine('X-Health-Token'),
            ProductionGuards::shouldEnforce(),
            $this->request->clientIp() ?: 'unknown',
        );
        if ($denied !== null) {
            return $this->healthResponse($denied, false);
        }

        try {
            $connection = ConnectionManager::get('default');
            if (!$connection instanceof Connection) {
                throw new RuntimeException('Readiness requires a SQL connection.');
            }
            $connection->execute('SELECT 1');
            $throttle = Cache::getConfig('login_throttle');
            if (ProductionGuards::shouldEnforce()) {
                ProductionGuards::assertReady();
            } elseif (is_array($throttle) && ProductionGuards::isRedisStore($throttle)) {
                ProductionGuards::probeRateLimitStore();
            }
        } catch (Throwable) {
            return $this->healthResponse(503, false);
        }

        return $this->healthResponse(200, true);
    }

    /**
     * @param int $status HTTP status.
     * @param bool $ok Readiness result.
     * @return \Cake\Http\Response
     */
    private function healthResponse(int $status, bool $ok): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withHeader('Cache-Control', 'no-store')
            ->withStringBody($ok ? '{"ok":true}' : '{"ok":false}');
    }
}
