<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Integer-cent money helpers. Amounts are never stored or summed as floats.
 */
final class Money
{
    /**
     * Format integer cents as an Australian dollar string.
     *
     * @param int $cents Amount in cents, which may be negative.
     * @return string
     */
    public static function formatAud(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);
        $dollars = intdiv($absolute, 100);
        $remainder = $absolute % 100;

        return $sign . '$' . number_format($dollars, 0, '.', ',')
            . '.' . str_pad((string)$remainder, 2, '0', STR_PAD_LEFT);
    }

    /**
     * GST portion of a GST-inclusive amount, using bankers-style half-up via
     * integer rounding against the rate stored as millionths.
     *
     * @param int $inclusiveCents Line total that already includes GST.
     * @param string $taxRate Decimal string such as "0.10000".
     * @return int Tax portion in cents.
     */
    public static function gstPortionInclusive(int $inclusiveCents, string $taxRate): int
    {
        if ($inclusiveCents === 0) {
            return 0;
        }

        $millionths = self::rateToMillionths($taxRate);
        if ($millionths <= 0) {
            return 0;
        }

        $denominator = 1_000_000 + $millionths;

        return intdiv($inclusiveCents * $millionths + intdiv($denominator, 2), $denominator);
    }

    /**
     * Convert a decimal tax rate string into millionths without using floats.
     *
     * @param string $rate Rate such as "0.10000" or "0.1".
     * @return int
     */
    public static function rateToMillionths(string $rate): int
    {
        $rate = trim($rate);
        if ($rate === '') {
            return 0;
        }

        $negative = str_starts_with($rate, '-');
        if ($negative) {
            $rate = substr($rate, 1);
        }

        $parts = explode('.', $rate, 2);
        $whole = (int)($parts[0] !== '' ? $parts[0] : '0');
        $fraction = isset($parts[1]) ? substr(str_pad($parts[1], 6, '0'), 0, 6) : '000000';
        $value = $whole * 1_000_000 + (int)$fraction;

        return $negative ? -$value : $value;
    }
}
