<?php
declare(strict_types=1);

use App\Service\Inventory\InventoryLedger;
use App\Service\Orders\OrderService;
use App\Service\Payments\RefundService;
use App\Service\Payments\StripePaymentGateway;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/config/bootstrap.php';

$providerRefundId = (string)($argv[1] ?? '');
$status = (string)($argv[2] ?? '');
$intentId = (string)($argv[3] ?? '');
$amountCents = (int)($argv[4] ?? 0);
$localRefundId = (string)($argv[5] ?? '');

$service = new RefundService(new OrderService(new InventoryLedger()), new StripePaymentGateway());
$metadata = [];
if ($localRefundId !== '') {
    $metadata['local_refund_id'] = $localRefundId;
}
$service->applyWebhookStatus(
    $providerRefundId,
    $status,
    $intentId,
    $amountCents,
    'aud',
    $metadata,
);
fwrite(STDOUT, "ok\n");
