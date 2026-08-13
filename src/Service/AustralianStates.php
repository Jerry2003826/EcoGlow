<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Australian state and territory abbreviations for address forms.
 */
final class AustralianStates
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'ACT' => 'Australian Capital Territory',
            'NSW' => 'New South Wales',
            'NT' => 'Northern Territory',
            'QLD' => 'Queensland',
            'SA' => 'South Australia',
            'TAS' => 'Tasmania',
            'VIC' => 'Victoria',
            'WA' => 'Western Australia',
        ];
    }

    /**
     * @param string $code Posted state code.
     * @return bool
     */
    public static function isValid(string $code): bool
    {
        return isset(self::options()[$code]);
    }
}
