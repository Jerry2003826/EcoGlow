<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Masks customer contact details for roles without customers.view.
 */
final class ContactMask
{
    /**
     * Australian-style phone mask, e.g. 0400000089 → 04** *** *89.
     *
     * @param string|null $phone Raw phone.
     * @return string
     */
    public static function phone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string)$phone) ?? '';
        if ($digits === '') {
            return '—';
        }
        if (strlen($digits) < 4) {
            return str_repeat('*', strlen($digits));
        }
        $prefix = substr($digits, 0, 2);
        $suffix = substr($digits, -2);
        if (strlen($digits) === 10) {
            return $prefix . '** *** *' . $suffix;
        }

        return $prefix . str_repeat('*', strlen($digits) - 4) . $suffix;
    }

    /**
     * Mask an email, keeping the first local character and the domain.
     *
     * @param string|null $email Raw email.
     * @return string
     */
    public static function email(?string $email): string
    {
        $email = trim((string)$email);
        if ($email === '' || !str_contains($email, '@')) {
            return $email === '' ? '—' : '***';
        }
        [$local, $domain] = explode('@', $email, 2);
        $first = $local !== '' ? $local[0] : '*';

        return $first . '***@' . $domain;
    }
}
