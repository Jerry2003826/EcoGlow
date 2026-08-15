<?php
declare(strict_types=1);

namespace App\Service\Payments;

/**
 * Outcome of an automatic capture reversal.
 */
final class ReversalResult
{
    /**
     * @param string $status pending|succeeded|failed
     * @param int $refundId Local payment_refunds id, or 0 when none exists.
     * @param string|null $providerRefundId Stripe refund id when known.
     */
    public function __construct(
        public readonly string $status,
        public readonly int $refundId,
        public readonly ?string $providerRefundId = null,
    ) {
    }
}
