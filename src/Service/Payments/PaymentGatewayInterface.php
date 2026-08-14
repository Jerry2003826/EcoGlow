<?php
declare(strict_types=1);

namespace App\Service\Payments;

/**
 * Stripe (or a test double) for PaymentIntents and refunds.
 */
interface PaymentGatewayInterface
{
    /**
     * @param int $amountCents Server-computed amount.
     * @param string $currency ISO currency, e.g. aud.
     * @param array<string, string> $metadata Values stored on the PaymentIntent.
     * @return \App\Service\Payments\PaymentIntentResult
     */
    public function createPaymentIntent(
        int $amountCents,
        string $currency,
        array $metadata,
        ?string $idempotencyKey = null,
    ): PaymentIntentResult;

    /**
     * @param string $paymentIntentId Stripe PaymentIntent id.
     * @param int $amountCents Amount to refund.
     * @param string $idempotencyKey Stripe idempotency key.
     * @return \App\Service\Payments\RefundResult
     */
    public function refund(string $paymentIntentId, int $amountCents, string $idempotencyKey): RefundResult;

    /**
     * Client secret for an unpaid PaymentIntent, or null when it cannot be paid.
     *
     * @param string $paymentIntentId Stripe PaymentIntent id.
     * @return string|null
     */
    public function retrieveClientSecret(string $paymentIntentId): ?string;
}
