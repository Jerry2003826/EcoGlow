<?php
declare(strict_types=1);

namespace App\Service\Payments;

use Cake\Core\Configure;
use InvalidArgumentException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Live Stripe API. Tests replace this via Configure Stripe.gateway.
 */
class StripePaymentGateway implements PaymentGatewayInterface
{
    /**
     * @param \Stripe\StripeClient|null $client Injected client, or built from config.
     */
    public function __construct(private ?StripeClient $client = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function createPaymentIntent(int $amountCents, string $currency, array $metadata): PaymentIntentResult
    {
        if ($amountCents < 1) {
            throw new InvalidArgumentException('A payment must be at least 1 cent.');
        }

        try {
            $intent = $this->client()->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $metadata,
            ]);
        } catch (ApiErrorException $exception) {
            throw new InvalidArgumentException(
                'The payment service could not start this charge. Please try again.',
                0,
                $exception,
            );
        }

        return new PaymentIntentResult((string)$intent->id, (string)$intent->client_secret);
    }

    /**
     * @inheritDoc
     */
    public function refund(string $paymentIntentId, int $amountCents, string $idempotencyKey): RefundResult
    {
        try {
            $refund = $this->client()->refunds->create(
                [
                    'payment_intent' => $paymentIntentId,
                    'amount' => $amountCents,
                ],
                ['idempotency_key' => $idempotencyKey],
            );
        } catch (ApiErrorException $exception) {
            throw new InvalidArgumentException(
                'The refund could not be completed. Please try again.',
                0,
                $exception,
            );
        }

        return new RefundResult((string)$refund->id, (string)$refund->status);
    }

    /**
     * @return \Stripe\StripeClient
     */
    private function client(): StripeClient
    {
        if ($this->client instanceof StripeClient) {
            return $this->client;
        }
        $secret = (string)Configure::read('Stripe.secretKey');
        if ($secret === '') {
            throw new InvalidArgumentException(
                'Stripe is not configured. Set STRIPE_SECRET_KEY in the environment.',
            );
        }
        $this->client = new StripeClient($secret);

        return $this->client;
    }
}
