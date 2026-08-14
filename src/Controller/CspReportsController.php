<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;
use Cake\Log\Log;

/**
 * Receives browser CSP reports. CSRF is skipped for this path only.
 */
class CspReportsController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['report']);
        $this->viewBuilder()->disableAutoLayout();
    }

    /**
     * @return \Cake\Http\Response
     */
    public function report(): Response
    {
        $this->request->allowMethod(['post']);
        $stream = $this->request->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $payload = $stream->getContents();
        Log::warning('CSP report received', [
            'bytes' => strlen($payload),
            'digest' => hash('sha256', $payload),
        ]);

        return $this->response->withStatus(204);
    }
}
