<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\Money;
use Cake\View\Helper;

/**
 * View wrapper around integer-cent formatting.
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class MoneyHelper extends Helper
{
    /**
     * Format cents as AUD.
     *
     * @param string|int|null $cents Amount in cents.
     * @return string
     */
    public function aud(int|string|null $cents): string
    {
        return Money::formatAud((int)$cents);
    }
}
