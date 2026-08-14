<?php
declare(strict_types=1);

namespace App\Service\Security;

use RuntimeException;

/**
 * Rejects placeholder seeder passwords so public defaults cannot be deployed.
 */
final class SeedPassword
{
    /**
     * @var list<string>
     */
    private const BLOCKED = [
        'admin123',
        'customer123',
        'password',
        'changeme',
        'secret',
        '12345678',
        '123456789',
        'qwerty',
    ];

    /**
     * @param string $envName Environment variable name.
     * @return string
     */
    public static function require(string $envName): string
    {
        $password = trim((string)env($envName, ''));
        if (strlen($password) < 20 || in_array(strtolower($password), self::BLOCKED, true)) {
            throw new RuntimeException(
                $envName . ' must be a non-placeholder value of at least 20 characters.',
            );
        }

        return $password;
    }
}
