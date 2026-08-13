<?php
declare(strict_types=1);

namespace App\Service\Payments;

use Cake\Core\Configure;

/**
 * Resolves the payment gateway, preferring a test double when configured.
 */
final class PaymentGatewayFactory
{
    /**
     * @return \App\Service\Payments\PaymentGatewayInterface
     */
    public static function create(): PaymentGatewayInterface
    {
        $configured = Configure::read('Stripe.gateway');
        if ($configured instanceof PaymentGatewayInterface) {
            return $configured;
        }

        return new StripePaymentGateway();
    }
}
