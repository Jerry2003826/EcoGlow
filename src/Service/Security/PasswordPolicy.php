<?php
declare(strict_types=1);

namespace App\Service\Security;

/**
 * Shared password rules for registration, reset, and account updates.
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /**
     * @var list<string>
     */
    private const BLOCKED = [
        'password',
        'password1',
        'password12',
        'password123',
        'admin123',
        'customer123',
        'changeme',
        '12345678',
        '123456789',
        '1234567890',
        'qwerty',
        'qwerty123',
        'letmein',
        'welcome',
        'iloveyou',
        'ecoglow',
        'ecoglow123',
    ];

    /**
     * @param string $password Candidate password.
     * @return bool
     */
    public static function isRejected(string $password): bool
    {
        if (strlen($password) < self::MIN_LENGTH) {
            return true;
        }

        return in_array(strtolower($password), self::BLOCKED, true);
    }
}
