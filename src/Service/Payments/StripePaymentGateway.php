<?php
declare(strict_types=1);

namespace App\Service\Payments;

use Cake\Core\Configure;
use InvalidArgumentException;
use Stripe\Exception\ApiConnectionException;
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
    public function createPaymentIntent(
        int $amountCents,
        string $currency,
        array $metadata,
        ?string $idempotencyKey = null,
    ): PaymentIntentResult {
        if ($amountCents < 1) {
            throw new InvalidArgumentException('A payment must be at least 1 cent.');
        }

        try {
            $options = [];
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $options['idempotency_key'] = $idempotencyKey;
            }
            $intent = $this->client()->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
            ], $options);
        } catch (ApiErrorException $exception) {
            $this->rethrowStripe($exception, 'The payment service could not start this charge. Please try again.');
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
            $this->rethrowStripe($exception, 'The refund could not be completed. Please try again.');
        }

        return new RefundResult((string)$refund->id, (string)$refund->status);
    }

    /**
     * @inheritDoc
     */
    public function retrieveClientSecret(string $paymentIntentId): ?string
    {
        if ($paymentIntentId === '') {
            return null;
        }

        try {
            $intent = $this->client()->paymentIntents->retrieve($paymentIntentId);
        } catch (ApiErrorException $exception) {
            $this->rethrowStripe($exception, 'The payment service could not load this charge. Please try again.');
        }

        $status = (string)$intent->status;
        if (in_array($status, ['succeeded', 'canceled'], true)) {
            return null;
        }

        $secret = (string)$intent->client_secret;

        return $secret !== '' ? $secret : null;
    }

    /**
     * @inheritDoc
     */
    public function retrieveRefund(string $refundId): ?RefundResult
    {
        if ($refundId === '') {
            return null;
        }

        try {
            $refund = $this->client()->refunds->retrieve($refundId);
        } catch (ApiErrorException $exception) {
            $this->rethrowStripe($exception, 'The payment service could not load this refund. Please try again.');
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

    /**
     * @param \Stripe\Exception\ApiErrorException $exception Stripe error.
     * @param string $message Public message.
     * @return never
     */
    private function rethrowStripe(ApiErrorException $exception, string $message): never
    {
        if ($this->isUncertain($exception)) {
            throw new PaymentUncertainException($message, 0, $exception);
        }

        throw new InvalidArgumentException($message, 0, $exception);
    }

    /**
     * @param \Stripe\Exception\ApiErrorException $exception Stripe error.
     * @return bool
     */
    private function isUncertain(ApiErrorException $exception): bool
    {
        if ($exception instanceof ApiConnectionException) {
            return true;
        }
        $status = $exception->getHttpStatus();

        return $status === null || $status >= 500 || $status === 429;
    }
}
