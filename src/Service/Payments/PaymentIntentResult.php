<?php
declare(strict_types=1);

namespace App\Service\Payments;

/**
 * Client-facing PaymentIntent fields. Amounts stay on the server.
 */
final class PaymentIntentResult
{
    /**
     * @param string $id Stripe PaymentIntent id.
     * @param string $clientSecret Stripe.js client secret.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $clientSecret,
    ) {
    }
}
