<?php

declare(strict_types=1);

namespace App\Service;

final class Money
{

    public static function formatAud(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $abs = abs($cents);
        $dollars = intdiv($abs, 100);
        $pence = $abs % 100;

        return $sign . '$' . number_format($dollars, 0, '.', ',')
            . '.' . str_pad((string)$pence, 2, '0', STR_PAD_LEFT);
    }
}
