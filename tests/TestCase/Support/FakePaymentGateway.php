<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

use App\Service\Payments\PaymentGatewayInterface;
use App\Service\Payments\PaymentIntentResult;
use App\Service\Payments\PaymentUncertainException;
use App\Service\Payments\RefundResult;
use InvalidArgumentException;

/**
 * In-process Stripe stand-in. Never opens a network socket.
 */
final class FakePaymentGateway implements PaymentGatewayInterface
{
    /**
     * Last PaymentIntent amount in cents.
     *
     * @var int
     */
    public int $lastAmountCents = 0;

    /**
     * @var list<array<string, mixed>>
     */
    public array $intents = [];

    /**
     * @var string
     */
    public string $nextIntentId = 'pi_test_1';

    /**
     * @var string|null
     */
    public ?string $lastIdempotencyKey = null;

    /**
     * @var bool
     */
    public bool $throwOnCreate = false;

    /**
     * @var bool
     */
    public bool $uncertainOnCreate = false;

    /**
     * @var string
     */
    public string $refundStatus = 'succeeded';

    /**
     * @var string
     */
    public string $nextRefundId = 're_test_1';

    /**
     * @inheritDoc
     */
    public function createPaymentIntent(
        int $amountCents,
        string $currency,
        array $metadata,
        ?string $idempotencyKey = null,
    ): PaymentIntentResult {
        $this->lastAmountCents = $amountCents;
        $this->lastIdempotencyKey = $idempotencyKey;
        if ($this->uncertainOnCreate) {
            throw new PaymentUncertainException('The payment service timed out.');
        }
        if ($this->throwOnCreate) {
            throw new InvalidArgumentException('The card was declined.');
        }
        $id = $this->nextIntentId;
        $this->intents[] = [
            'id' => $id,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'metadata' => $metadata,
            'idempotency_key' => $idempotencyKey,
        ];

        return new PaymentIntentResult($id, $id . '_secret_test');
    }

    /**
     * @inheritDoc
     */
    public function refund(string $paymentIntentId, int $amountCents, string $idempotencyKey): RefundResult
    {
        return new RefundResult($this->nextRefundId, $this->refundStatus);
    }

    /**
     * @inheritDoc
     */
    public function retrieveClientSecret(string $paymentIntentId): ?string
    {
        if ($paymentIntentId === '' || str_starts_with($paymentIntentId, 'pi_done_')) {
            return null;
        }

        return $paymentIntentId . '_secret_test';
    }
}
