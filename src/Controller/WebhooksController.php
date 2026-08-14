<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\OutboundQueue;
use App\Service\Payments\IdempotencyService;
use App\Service\Payments\StripeWebhookService;
use App\Service\Payments\StripeWebhookVerifier;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Stripe webhook endpoint. CSRF is skipped for this path only.
 */
class WebhooksController extends AppController
{
    /**
     * Stripe bodies are small JSON events. Larger payloads are refused.
     *
     * @var int
     */
    private const MAX_BODY_BYTES = 262144;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['stripe']);
        $this->viewBuilder()->disableAutoLayout();
    }

    /**
     * @return \Cake\Http\Response
     */
    public function stripe(): Response
    {
        $this->request->allowMethod(['post']);
        $signature = $this->request->getHeaderLine('Stripe-Signature');

        try {
            $payload = $this->rawBody();
            $event = (new StripeWebhookVerifier())->parse($payload, $signature);
        } catch (InvalidArgumentException $exception) {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody('{"error":"invalid_webhook"}');
        }

        $service = new StripeWebhookService(
            new OrderService(new InventoryLedger()),
            new IdempotencyService(),
            new OutboundQueue(),
        );
        $result = $service->handle($event);

        return $this->response
            ->withStatus($result['status'])
            ->withType('application/json')
            ->withStringBody(json_encode($result['body']) ?: '{"received":true}');
    }

    /**
     * Raw body for HMAC verification. Parsed JSON is not a substitute.
     *
     * @return string
     */
    private function rawBody(): string
    {
        $stream = $this->request->getBody();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $payload = $stream->getContents();
        if (strlen($payload) > self::MAX_BODY_BYTES) {
            throw new InvalidArgumentException('payload_too_large');
        }

        return $payload;
    }
}
