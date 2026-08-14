#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * One-click local bootstrap: write app_local.php, migrate, seed, then serve.
 *
 * Usage: php bin/dev_up.php [--no-serve] [--port=8765]
 */

$root = dirname(__DIR__);
chdir($root);

$options = getopt('', ['no-serve', 'port:']);
$port = isset($options['port']) ? (int)$options['port'] : (int)(getenv('ECOGLOW_PORT') ?: 8765);
if ($port < 1 || $port > 65535) {
    $port = 8765;
}
$serve = !array_key_exists('no-serve', $options);

fwrite(STDOUT, "Eco Glow — local one-click setup\n");
fwrite(STDOUT, str_repeat('=', 40) . "\n");

$php = PHP_BINARY;
if (PHP_VERSION_ID < 80200) {
    fwrite(STDERR, "PHP 8.2+ is required. This runtime is " . PHP_VERSION . ".\n");
    exit(1);
}
if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "The PHP PDO MySQL driver is missing. Install php-mysql / pdo_mysql and retry.\n");
    exit(1);
}

ensureWritableDirs($root);
$account = ensureDatabase();
writeAppLocal($root, $account);
$composer = ensureComposer($root, $php);
runArgv(array_merge($composer, ['install', '--no-interaction', '--prefer-dist']), $root);
putenv('MASTER_USER_EMAIL=admin@ecoglow.local');
putenv('ADMIN_SEED_PASSWORD=admin123');
putenv('CUSTOMER_SEED_PASSWORD=customer123');
putenv('RECAPTCHA_ENABLED=false');

runArgv([$php, $root . '/bin/cake.php', 'migrations', 'migrate'], $root);
foreach (['UsersSeed', 'EcoGlowAuthorizationSeed', 'FrontendCatalogSeed', 'FrontendInventorySeed', 'DemoCustomerSeed'] as $seed) {
    runArgv([$php, $root . '/bin/cake.php', 'seeds', 'run', '--seed', $seed], $root);
}

$url = 'http://127.0.0.1:' . $port;
fwrite(STDOUT, "\nReady.\n");
fwrite(STDOUT, "  Storefront  {$url}\n");
fwrite(STDOUT, "  Staff login {$url}/login\n");
fwrite(STDOUT, "      admin@ecoglow.local / admin123\n");
fwrite(STDOUT, "  Customer    {$url}/account/login\n");
fwrite(STDOUT, "      customer@ecoglow.local / customer123\n");
fwrite(STDOUT, "  Stripe is optional. Add test keys to config/app_local.php to pay.\n\n");

if (!$serve) {
    exit(0);
}

openBrowser($url);
fwrite(STDOUT, "Starting CakePHP server on {$url}\nPress Ctrl+C to stop.\n");
passthru(escapeshellarg($php) . ' ' . escapeshellarg($root . '/bin/cake.php') . ' server -H 127.0.0.1 -p ' . $port);
exit(0);

/**
 * @return array{host: string, port: int, user: string, password: string, database: string}
 */
function ensureDatabase(): array
{
    $preferred = [
        'host' => getenv('ECOGLOW_DB_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('ECOGLOW_DB_PORT') ?: 3306),
        'user' => 'ecoglow',
        'password' => 'ecoglow',
        'database' => getenv('ECOGLOW_DB_NAME') ?: 'ecoglow',
    ];

    $admin = null;
    for ($attempt = 1; $attempt <= 15; $attempt++) {
        $admin = findMysqlAdmin($preferred['host'], $preferred['port']);
        if ($admin instanceof PDO) {
            break;
        }
        fwrite(STDOUT, "Waiting for MySQL ({$attempt}/15)...\n");
        sleep(2);
    }
    if ($admin === null) {
        fwrite(STDERR, "Could not connect to MySQL/MariaDB on {$preferred['host']}:{$preferred['port']}.\n");
        fwrite(STDERR, "Start MySQL first, or set ECOGLOW_DB_HOST / MYSQL_ROOT_PASSWORD.\n");
        exit(1);
    }

    $admin->exec(
        'CREATE DATABASE IF NOT EXISTS `' . $preferred['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $appUser = $preferred['user'];
    $appPass = $preferred['password'];
    try {
        $admin->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY '{$appPass}'");
        $admin->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'127.0.0.1' IDENTIFIED BY '{$appPass}'");
        $admin->exec("GRANT ALL PRIVILEGES ON `{$preferred['database']}`.* TO '{$appUser}'@'localhost'");
        $admin->exec("GRANT ALL PRIVILEGES ON `{$preferred['database']}`.* TO '{$appUser}'@'127.0.0.1'");
        $admin->exec('FLUSH PRIVILEGES');
        probeMysql($preferred['host'], $preferred['port'], $appUser, $appPass, $preferred['database']);
        fwrite(STDOUT, "Database {$preferred['database']} is ready (user {$appUser}).\n");

        return $preferred;
    } catch (Throwable $exception) {
        fwrite(STDOUT, "Could not create app user, falling back to the admin MySQL account.\n");
        $preferred['user'] = $admin->query('SELECT CURRENT_USER()')->fetchColumn() ?: 'root';
        // CURRENT_USER() is like root@localhost — keep the login we actually used.
        $preferred['user'] = (string)($GLOBALS['_ecoglow_mysql_user'] ?? 'root');
        $preferred['password'] = (string)($GLOBALS['_ecoglow_mysql_password'] ?? '');
        fwrite(STDOUT, "Database {$preferred['database']} is ready (user {$preferred['user']}).\n");

        return $preferred;
    }
}

/**
 * @return PDO|null
 */
function findMysqlAdmin(string $host, int $port): ?PDO
{
    $passwords = [
        (string)(getenv('MYSQL_ROOT_PASSWORD') ?: ''),
        'root',
        'password',
        'secret',
        'ecoglow',
    ];
    $users = ['root', 'ecoglow', 'my_app'];
    $hosts = array_values(array_unique([$host, '127.0.0.1', 'localhost']));

    foreach ($hosts as $tryHost) {
        foreach ($users as $user) {
            foreach ($passwords as $password) {
                $pdo = probeMysql($tryHost, $port, $user, $password, null);
                if ($pdo instanceof PDO) {
                    $GLOBALS['_ecoglow_mysql_user'] = $user;
                    $GLOBALS['_ecoglow_mysql_password'] = $password;

                    return $pdo;
                }
            }
        }
    }

    return null;
}

function probeMysql(string $host, int $port, string $user, string $password, ?string $database): ?PDO
{
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port);
    if ($database !== null) {
        $dsn .= ';dbname=' . $database;
    }
    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3,
        ]);
        $pdo->query('SELECT 1');

        return $pdo;
    } catch (Throwable $exception) {
        return null;
    }
}

/**
 * @param array{host: string, port: int, user: string, password: string, database: string} $account
 */
function writeAppLocal(string $root, array $account): void
{
    $path = $root . '/config/app_local.php';
    $salt = bin2hex(random_bytes(32));
    if (is_file($path)) {
        $existing = (string)file_get_contents($path);
        if (preg_match("/'salt'\\s*=>\\s*'([^']+)'/", $existing, $match) === 1 && $match[1] !== '__SALT__') {
            $salt = $match[1];
        }
    }

    $export = static function (string $value): string {
        return var_export($value, true);
    };

    $contents = <<<PHP
<?php

use function Cake\\Core\\env;

return [
    'debug' => true,
    'Security' => [
        'salt' => {$export($salt)},
    ],
    'Datasources' => [
        'default' => [
            'host' => {$export($account['host'])},
            'port' => {$account['port']},
            'username' => {$export($account['user'])},
            'password' => {$export($account['password'])},
            'database' => {$export($account['database'])},
            'url' => env('DATABASE_URL', null),
        ],
        'test' => [
            'host' => {$export($account['host'])},
            'port' => {$account['port']},
            'username' => {$export($account['user'])},
            'password' => {$export($account['password'])},
            'database' => 'test_ecoglow',
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],
    'Recaptcha' => [
        'enabled' => false,
        'sitekey' => '',
        'secret' => '',
    ],
    'Stripe' => [
        'publishableKey' => env('STRIPE_PUBLISHABLE_KEY', ''),
        'secretKey' => env('STRIPE_SECRET_KEY', ''),
        'webhookSecret' => env('STRIPE_WEBHOOK_SECRET', ''),
    ],
];

PHP;

    file_put_contents($path, $contents);
    fwrite(STDOUT, "Wrote config/app_local.php for local demo use.\n");
}

function ensureWritableDirs(string $root): void
{
    foreach (['logs', 'tmp', 'tmp/cache', 'tmp/cache/models', 'tmp/cache/persistent', 'tmp/cache/views', 'tmp/sessions', 'tmp/tests'] as $relative) {
        $path = $root . '/' . $relative;
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            fwrite(STDERR, "Could not create {$path}\n");
            exit(1);
        }
        @chmod($path, 0777);
    }
}

/**
 * @return list<string>
 */
function ensureComposer(string $root, string $php): array
{
    $phar = $root . '/bin/composer.phar';
    if (is_file($phar)) {
        return [$php, $phar];
    }
    $global = findOnPath('composer');
    if ($global !== null) {
        return [$global];
    }
    fwrite(STDOUT, "Downloading Composer...\n");
    $url = 'https://getcomposer.org/download/latest-stable/composer.phar';
    $data = @file_get_contents($url);
    if ($data === false) {
        $tmp = $root . '/tmp/composer-setup.php';
        run($php, ['-r', 'copy("https://getcomposer.org/installer", ' . var_export($tmp, true) . ');'], $root);
        run($php, [$tmp, '--install-dir=' . $root . '/bin', '--filename=composer.phar'], $root);
        @unlink($tmp);
    } else {
        file_put_contents($phar, $data);
    }
    if (!is_file($phar)) {
        fwrite(STDERR, "Composer could not be downloaded. Install it from https://getcomposer.org/\n");
        exit(1);
    }

    return [$php, $phar];
}

function findOnPath(string $name): ?string
{
    $paths = explode(PATH_SEPARATOR, (string)getenv('PATH'));
    $suffixes = PHP_OS_FAMILY === 'Windows' ? ['.bat', '.cmd', '.exe', ''] : [''];
    foreach ($paths as $dir) {
        foreach ($suffixes as $suffix) {
            $candidate = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $name . $suffix;
            if (is_file($candidate)) {
                return $candidate;
            }
        }
    }

    return null;
}

/**
 * @param list<string> $args
 */
function run(string $php, array $args, string $cwd): void
{
    runArgv(array_merge([$php], $args), $cwd);
}

/**
 * @param list<string> $args
 */
function runArgv(array $args, string $cwd): void
{
    $command = '';
    foreach ($args as $arg) {
        $command .= ($command === '' ? '' : ' ') . escapeshellarg($arg);
    }
    fwrite(STDOUT, "> {$command}\n");
    $descriptor = [0 => STDIN, 1 => STDOUT, 2 => STDERR];
    $process = proc_open($command, $descriptor, $pipes, $cwd);
    if (!is_resource($process)) {
        fwrite(STDERR, "Failed to start: {$command}\n");
        exit(1);
    }
    $code = proc_close($process);
    if ($code !== 0) {
        fwrite(STDERR, "Command failed with exit code {$code}\n");
        exit($code);
    }
}

function openBrowser(string $url): void
{
    if (PHP_OS_FAMILY === 'Darwin') {
        proc_open('open ' . escapeshellarg($url), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        return;
    }
    if (PHP_OS_FAMILY === 'Windows') {
        proc_open('cmd /c start "" ' . escapeshellarg($url), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        return;
    }
    proc_open('xdg-open ' . escapeshellarg($url) . ' >/dev/null 2>&1', [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
}
