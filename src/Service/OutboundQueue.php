<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\OutboundMessage;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Writes rows to outbound_messages. Nothing here sends mail.
 */
class OutboundQueue
{
    use LocatorAwareTrait;

    /**
     * @param array<string, mixed> $payload Queue fields.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\OutboundMessage
     */
    public function enqueue(array $payload, int $actorUserId): OutboundMessage
    {
        $messages = $this->fetchTable('OutboundMessages');
        $row = $messages->newEmptyEntity();
        $row->reference_number = $payload['reference_number'];
        $row->customer_id = $payload['customer_id'] ?? null;
        $row->channel = $payload['channel'] ?? 'email';
        $row->recipient = $payload['recipient'];
        $row->template_key = $payload['template_key'] ?? null;
        $row->subject = $payload['subject'] ?? null;
        $row->body_text = $payload['body_text'] ?? null;
        $row->body_html = $payload['body_html'] ?? null;
        $row->status = 'queued';
        $row->related_entity_type = $payload['related_entity_type'] ?? null;
        $row->related_entity_id = $payload['related_entity_id'] ?? null;
        $row->metadata = $payload['metadata'] ?? [];
        $row->created_by_user_id = $actorUserId;
        $row->scheduled_at = DateTime::now('UTC');

        return $messages->saveOrFail($row);
    }
}
