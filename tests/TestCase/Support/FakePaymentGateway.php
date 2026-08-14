<?php
declare(strict_types=1);

namespace App\Test\TestCase\Support;

use App\Service\Payments\PaymentGatewayInterface;
use App\Service\Payments\PaymentIntentResult;
use App\Service\Payments\RefundResult;

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
        return new RefundResult('re_test_1', 'succeeded');
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
