<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Utility\Security;
use InvalidArgumentException;

/**
 * RFC 6238 TOTP (SHA-1, 30s, 6 digits) without a third-party package.
 */
final class TotpService
{
    private const PERIOD = 30;

    private const DIGITS = 6;

    /**
     * @return string Base32 secret.
     */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /**
     * @param string $secret Base32 secret.
     * @param string $email Account email for the otpauth label.
     * @return string
     */
    public static function provisioningUri(string $secret, string $email): string
    {
        $label = rawurlencode('EcoGlow:' . $email);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=EcoGlow&period=%d&digits=%d',
            $label,
            $secret,
            self::PERIOD,
            self::DIGITS,
        );
    }

    /**
     * @param string $secret Base32 secret.
     * @param string $code Submitted digits.
     * @return bool
     */
    public static function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }
        $window = (int)floor(time() / self::PERIOD);
        for ($offset = -1; $offset <= 1; $offset++) {
            if (hash_equals(self::at($secret, $window + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $plain Base32 secret.
     * @return string Encrypted payload.
     */
    public static function seal(string $plain): string
    {
        $sealed = Security::encrypt($plain, self::key());
        if ($sealed === false) {
            throw new InvalidArgumentException('The MFA secret could not be stored.');
        }

        return base64_encode($sealed);
    }

    /**
     * @param string $sealed Encrypted payload.
     * @return string
     */
    public static function open(string $sealed): string
    {
        $raw = base64_decode($sealed, true);
        if ($raw === false || $raw === '') {
            throw new InvalidArgumentException('The MFA secret is unreadable.');
        }
        $plain = Security::decrypt($raw, self::key());
        if ($plain === false || $plain === '') {
            throw new InvalidArgumentException('The MFA secret is unreadable.');
        }

        return $plain;
    }

    /**
     * @param string $secret Base32 secret.
     * @param int $counter Time step.
     * @return string
     */
    private static function at(string $secret, int $counter): string
    {
        $binary = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $time, $binary, true);
        $offset = ord($hash[19]) & 0x0F;
        $unpacked = unpack('N', substr($hash, $offset, 4));
        $value = is_array($unpacked) ? ($unpacked[1] & 0x7FFFFFFF) : 0;

        return str_pad((string)($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    private static function key(): string
    {
        return hash('sha256', Security::getSalt(), true);
    }

    /**
     * @param string $binary Raw bytes.
     * @return string
     */
    private static function base32Encode(string $binary): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0');
            }
            $encoded .= $alphabet[bindec($chunk)];
        }

        return $encoded;
    }

    /**
     * @param string $secret Base32 secret.
     * @return string
     */
    private static function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $bits = '';
        foreach (str_split($secret) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                continue;
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }
        $binary = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr((int)bindec($chunk));
            }
        }

        return $binary;
    }
}
