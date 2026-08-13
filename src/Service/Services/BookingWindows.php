<?php
declare(strict_types=1);

namespace App\Service\Services;

/**
 * Preferred call-out windows. Not a calendar.
 */
final class BookingWindows
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'morning' => 'Morning (8:00am–12:00pm)',
            'afternoon' => 'Afternoon (12:00pm–5:00pm)',
            'evening' => 'Evening (5:00pm–7:00pm)',
        ];
    }

    /**
     * @param string $key Window key.
     * @return bool
     */
    public static function isValid(string $key): bool
    {
        return isset(self::options()[$key]);
    }

    /**
     * @param string $key Window key.
     * @return string
     */
    public static function label(string $key): string
    {
        return self::options()[$key] ?? $key;
    }
}
