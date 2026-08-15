<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Security\ProductionGuards;
use Cake\Http\Response;

/**
 * Deployment readiness. CSRF is skipped for this path only.
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
        if (ProductionGuards::shouldEnforce()) {
            ProductionGuards::assertReady();
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody('{"ok":true}');
    }
}
