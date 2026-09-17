<?php
// src/Mailer/QuotationMailer.php

declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class QuotationMailer extends Mailer
{
    public function quotationIssued(object $quotation): static
    {
        $customerName = (string)($quotation->customer->CustomerName ?? 'Customer');
        $email = (string)($quotation->customer->Email ?? '');

        $this
            ->setTo($email)
            ->setSubject('Eco Glow quotation QT-' . (string)$quotation->QuotationId)
            ->setEmailFormat('both')
            ->setViewVars([
                'quotation' => $quotation,
                'customerName' => $customerName,
            ])
            ->viewBuilder()
            ->setTemplate('quotation');

        return $this;
    }
}
