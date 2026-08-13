<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\OutboundMessage;
use Cake\Mailer\Mailer;
use Cake\Mailer\Message;

/**
 * Renders queued customer emails (enquiry replies and invoices).
 */
class OutboundMailer extends Mailer
{
    /**
     * Mailer name.
     *
     * @var string
     */
    public static string $name = 'Outbound';

    /**
     * Reply to a public contact enquiry.
     *
     * @param \App\Model\Entity\OutboundMessage $message Queue row.
     * @return void
     */
    public function contactReply(OutboundMessage $message): void
    {
        $this->compose($message, 'contact_reply', [
            'bodyText' => (string)$message->get('body_text'),
            'subject' => (string)$message->get('subject'),
        ]);
    }

    /**
     * Invoice notification.
     *
     * @param \App\Model\Entity\OutboundMessage $message Queue row.
     * @return void
     */
    public function invoice(OutboundMessage $message): void
    {
        $meta = $message->get('metadata');
        $invoiceNumber = is_array($meta) ? (string)($meta['invoice_number'] ?? '') : '';

        $this->compose($message, 'invoice', [
            'bodyText' => (string)$message->get('body_text'),
            'subject' => (string)$message->get('subject'),
            'invoiceNumber' => $invoiceNumber,
        ]);
    }

    /**
     * Order confirmation after a captured web payment.
     *
     * @param \App\Model\Entity\OutboundMessage $message Queue row.
     * @return void
     */
    public function orderConfirmation(OutboundMessage $message): void
    {
        $meta = $message->get('metadata');
        $orderNumber = is_array($meta) ? (string)($meta['order_number'] ?? '') : '';

        $this->compose($message, 'order_confirmation', [
            'bodyText' => (string)$message->get('body_text'),
            'subject' => (string)$message->get('subject'),
            'orderNumber' => $orderNumber,
        ]);
    }

    /**
     * Shared envelope for a queued row.
     *
     * @param \App\Model\Entity\OutboundMessage $message Queue row.
     * @param string $template Template name under templates/email.
     * @param array<string, mixed> $vars View variables.
     * @return void
     */
    private function compose(OutboundMessage $message, string $template, array $vars): void
    {
        $this
            ->setTo((string)$message->get('recipient'))
            ->setSubject((string)($message->get('subject') ?: 'Eco Glow Lighting'))
            ->setEmailFormat(Message::MESSAGE_BOTH)
            ->setViewVars($vars);

        $this->viewBuilder()->setTemplate($template);
    }
}
