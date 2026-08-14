<?php
declare(strict_types=1);

use App\Application;
use App\Service\Security\ProductionGuards;
use Cake\Cache\Cache;
use Cake\Mailer\TransportFactory;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';

$app = new Application($root . '/config');
$app->bootstrap();

$config = Cache::getConfig('login_throttle');
$mail = TransportFactory::getConfig('default');
fwrite(STDOUT, json_encode([
    'ok' => true,
    'redis' => is_array($config) && ProductionGuards::isRedisStore($config),
    'email' => is_array($mail) ? (string)($mail['className'] ?? '') : '',
], JSON_THROW_ON_ERROR) . "\n");
