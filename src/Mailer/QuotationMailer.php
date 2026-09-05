<?php

declare(strict_types=1);

namespace App\Mailer;

use App\Service\Money;
use Cake\Mailer\Mailer;

class QuotationMailer extends Mailer
{
    public function quotationIssued(object $quotation): static
    {
        $customerName = trim(
            (($quotation->customer->first_name ?? '') . ' ') .
            ($quotation->customer->last_name ?? '')
        );

        if ($customerName === '') {
            $customerName = (string)($quotation->customer->email ?? 'Customer');
        }

        $lines = [];

        foreach ($quotation->quotation_items as $item) {
            $lines[] = sprintf(
                '%s (%s) x %d = %s',
                (string)$item->product_name,
                (string)$item->sku,
                (int)$item->quantity,
                Money::formatAud((int)$item->line_total_cents)
            );
        }

        $this
            ->setTo((string)$quotation->customer->email)
            ->setSubject('Quotation ' . (string)$quotation->quotation_number)
            ->setEmailFormat('both')
            ->setViewVars([
                'quotation' => $quotation,
                'customerName' => $customerName,
                'itemsSummary' => implode("\n", $lines),
            ])
            ->viewBuilder()
            ->setTemplate('quotation');

        return $this;
    }
}
