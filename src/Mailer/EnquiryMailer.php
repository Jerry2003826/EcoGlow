<?php
// src/Mailer/EnquiryMailer.php

declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class EnquiryMailer extends Mailer
{
    public function enquiryContact(
        object $enquiry,
        string $subject,
        string $message,
        string $senderEmail
    ): static {
        $this
            ->setTo((string)$enquiry->Email)
            ->setSubject($subject)
            ->setEmailFormat('both')
            ->setViewVars([
                'enquiryName' => (string)$enquiry->Name,
                'subjectText' => $subject,
                'messageText' => $message,
                'senderEmail' => $senderEmail,
            ])
            ->viewBuilder()
            ->setTemplate('enquiry_contact');

        return $this;
    }
}
