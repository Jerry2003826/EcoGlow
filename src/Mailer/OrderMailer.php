<?php
// src/Mailer/OrderMailer.php

declare(strict_types=1);

namespace App\Mailer;

use Cake\Mailer\Mailer;

class OrderMailer extends Mailer
{
    public function orderConfirmation(object $order): static
    {
        $customerName = (string)($order->customer->CustomerName
            ?? $order->guest->GuestName
            ?? 'Customer');
        $email = (string)($order->customer->Email
            ?? $order->guest->Email
            ?? '');

        $this
            ->setTo($email)
            ->setSubject('Eco Glow order confirmation #' . (string)$order->OrderId)
            ->setEmailFormat('both')
            ->setViewVars([
                'order' => $order,
                'customerName' => $customerName,
            ])
            ->viewBuilder()
            ->setTemplate('order_confirmation');

        return $this;
    }
}
