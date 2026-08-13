<?php
declare(strict_types=1);

namespace App\Service\Payments;

use Cake\Core\Configure;
use InvalidArgumentException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Verifies Stripe-Signature locally. No network call.
 */
class StripeWebhookVerifier
{
    /**
     * @param string $payload Raw request body.
     * @param string $signatureHeader Stripe-Signature header.
     * @return \Stripe\Event
     */
    public function parse(string $payload, string $signatureHeader): Event
    {
        $secret = (string)Configure::read('Stripe.webhookSecret');
        if ($secret === '') {
            throw new InvalidArgumentException('Stripe webhook secret is not configured.');
        }
        if ($payload === '' || $signatureHeader === '') {
            throw new InvalidArgumentException('Stripe webhook signature is missing.');
        }

        try {
            return Webhook::constructEvent($payload, $signatureHeader, $secret);
        } catch (UnexpectedValueException | SignatureVerificationException) {
            throw new InvalidArgumentException('Stripe webhook signature is invalid.');
        }
    }
}
