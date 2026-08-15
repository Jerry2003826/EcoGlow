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
     * Create the intent locally, then throw as if the HTTP response timed out.
     *
     * @var bool
     */
    public bool $createThenTimeout = false;

    /**
     * @var bool
     */
    public bool $throwOnRefund = false;

    /**
     * @var array<string, \App\Service\Payments\PaymentIntentResult>
     */
    public array $intentsByKey = [];

    /**
     * @var array<string, string>
     */
    public array $refundsById = [];

    /**
     * @var string
     */
    public string $refundStatus = 'succeeded';

    /**
     * @var string
     */
    public string $nextRefundId = 're_test_1';

    /**
     * @var string
     */
    public string $lastRefundIdempotencyKey = '';

    /**
     * @var array<string, string>
     */
    public array $lastRefundMetadata = [];

    /**
     * @var list<string>
     */
    public array $canceledIntentIds = [];

    /**
     * @var array<string, string>
     */
    public array $intentStatusById = [];

    /**
     * @var string
     */
    public string $lastRefundPaymentIntentId = '';

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
        if ($idempotencyKey !== null && $idempotencyKey !== '' && isset($this->intentsByKey[$idempotencyKey])) {
            return $this->intentsByKey[$idempotencyKey];
        }
        if ($this->uncertainOnCreate) {
            throw new PaymentUncertainException('The payment service timed out.');
        }
        if ($this->throwOnCreate) {
            throw new InvalidArgumentException('The card was declined.');
        }
        $id = $this->nextIntentId;
        $result = new PaymentIntentResult($id, $id . '_secret_test');
        $this->intents[] = [
            'id' => $id,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'metadata' => $metadata,
            'idempotency_key' => $idempotencyKey,
        ];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $this->intentsByKey[$idempotencyKey] = $result;
        }
        if ($this->createThenTimeout) {
            $this->createThenTimeout = false;
            throw new PaymentUncertainException('The payment service timed out.');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function refund(
        string $paymentIntentId,
        int $amountCents,
        string $idempotencyKey,
        array $metadata = [],
    ): RefundResult {
        if ($this->throwOnRefund) {
            throw new InvalidArgumentException('The refund request did not reach Stripe.');
        }
        $this->lastRefundIdempotencyKey = $idempotencyKey;
        $this->lastRefundMetadata = $metadata;
        $this->lastRefundPaymentIntentId = $paymentIntentId;
        $this->refundsById[$this->nextRefundId] = $this->refundStatus;

        return new RefundResult($this->nextRefundId, $this->refundStatus, $amountCents, 'aud');
    }

    /**
     * @inheritDoc
     */
    public function retrieveClientSecret(string $paymentIntentId): ?string
    {
        if (
            $paymentIntentId === ''
            || str_starts_with($paymentIntentId, 'pi_done_')
            || in_array($paymentIntentId, $this->canceledIntentIds, true)
            || in_array($this->intentStatusById[$paymentIntentId] ?? '', ['canceled', 'succeeded'], true)
        ) {
            return null;
        }

        return $paymentIntentId . '_secret_test';
    }

    /**
     * @inheritDoc
     */
    public function retrieveRefund(string $refundId): ?RefundResult
    {
        if ($refundId === '' || !isset($this->refundsById[$refundId])) {
            return null;
        }

        return new RefundResult($refundId, $this->refundsById[$refundId], 0, 'aud');
    }

    /**
     * @inheritDoc
     */
    public function cancelPaymentIntent(string $paymentIntentId): string
    {
        if ($paymentIntentId === '') {
            return 'already_canceled';
        }
        $status = $this->intentStatusById[$paymentIntentId] ?? 'requires_payment_method';
        if ($status === 'canceled' || in_array($paymentIntentId, $this->canceledIntentIds, true)) {
            return 'already_canceled';
        }
        if (in_array($status, ['succeeded', 'processing'], true)) {
            return 'already_succeeded';
        }
        $this->canceledIntentIds[] = $paymentIntentId;
        $this->intentStatusById[$paymentIntentId] = 'canceled';

        return 'canceled';
    }
}
