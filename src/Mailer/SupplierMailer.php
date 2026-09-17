<?php
// src/Mailer/SupplierMailer.php

declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class SupplierMailer extends Mailer
{
    public function supplierContact(
        object $supplier,
        string $subject,
        string $message,
        string $senderEmail
    ): static {
        $this
            ->setTo((string)$supplier->Email)
            ->setSubject($subject)
            ->setEmailFormat('both')
            ->setViewVars([
                'supplierName' => (string)$supplier->SupplierName,
                'subjectText' => $subject,
                'messageText' => $message,
                'senderEmail' => $senderEmail,
            ])
            ->viewBuilder()
            ->setTemplate('supplier_contact');

        return $this;
    }
}
