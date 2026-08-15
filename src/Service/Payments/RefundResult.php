<?php
declare(strict_types=1);

namespace App\Service\Payments;

/**
 * Result of a Stripe refund call.
 */
final class RefundResult
{
    /**
     * @param string $id Stripe refund id.
     * @param string $status Stripe refund status.
     * @param int $amountCents Refunded amount.
     * @param string $currency Lowercase currency code.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly int $amountCents = 0,
        public readonly string $currency = 'aud',
    ) {
    }
}
