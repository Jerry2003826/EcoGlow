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
     */
    public function __construct(
        public readonly string $id,
        public readonly string $status,
    ) {
    }
}
