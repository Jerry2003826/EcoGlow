<?php
declare(strict_types=1);

namespace App\Service\Payments;

use RuntimeException;

/**
 * Stripe could not be reached or returned a retryable 5xx/429.
 *
 * The local order must stay on hold. Do not release inventory.
 */
class PaymentUncertainException extends RuntimeException
{
}
