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
    public const MAX_BODY_BYTES = 65536;

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
        $contentType = strtolower($this->request->getHeaderLine('Content-Type'));
        $allowedType = $contentType === ''
            || str_contains($contentType, 'json')
            || str_contains($contentType, 'csp-report')
            || str_contains($contentType, 'application/reports');
        if (!$allowedType) {
            return $this->response->withStatus(415);
        }
        $declared = (int)$this->request->getHeaderLine('Content-Length');
        if ($declared > self::MAX_BODY_BYTES) {
            return $this->response->withStatus(413);
        }
        $stream = $this->request->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $payload = $stream->read(self::MAX_BODY_BYTES + 1);
        if (strlen($payload) > self::MAX_BODY_BYTES) {
            return $this->response->withStatus(413);
        }
        Log::warning('CSP report received', [
            'bytes' => strlen($payload),
            'digest' => hash('sha256', $payload),
        ]);

        return $this->response->withStatus(204);
    }
}
