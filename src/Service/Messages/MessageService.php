<?php
declare(strict_types=1);

namespace App\Service\Messages;

use App\Model\Entity\ContactMessage;
use App\Service\Inventory\InventoryLedger;
use App\Service\OutboundQueue;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * Staff replies, assignment and status moves for contact_messages.
 *
 * Status / is_read / assigned_to_user_id are set() from here, never patched.
 */
class MessageService
{
    use LocatorAwareTrait;

    /**
     * @param \App\Service\OutboundQueue $queue Outbound mail queue.
     * @param \App\Service\Inventory\InventoryLedger $ledger Document numbers.
     */
    public function __construct(
        private OutboundQueue $queue,
        private InventoryLedger $ledger,
    ) {
    }

    /**
     * Queue a reply, record the event, and mark the enquiry resolved.
     *
     * @param \App\Model\Entity\ContactMessage $message Enquiry.
     * @param string $body Reply text.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\ContactMessage
     */
    public function reply(ContactMessage $message, string $body, int $actorUserId): ContactMessage
    {
        $body = trim($body);
        if ($body === '') {
            throw new InvalidArgumentException('Write a reply before sending.');
        }

        return $this->connection()->transactional(function () use ($message, $body, $actorUserId) {
            $reference = $this->ledger->nextDocumentNumber('outbound_message', 'OUT');
            $this->queue->enqueue([
                'reference_number' => $reference,
                'customer_id' => $message->customer_id,
                'channel' => 'email',
                'recipient' => $message->email,
                'template_key' => 'contact_reply',
                'subject' => 'Re: ' . $message->subject,
                'body_text' => $body,
                'related_entity_type' => 'contact_message',
                'related_entity_id' => $message->id,
                'metadata' => ['contact_message_id' => $message->id],
            ], $actorUserId);

            $this->recordEvent($message, $actorUserId, 'email', 'outbound', $body);

            $message->set('status', ContactMessage::STATUS_RESOLVED);
            $message->set('is_read', true);
            $message->set('last_response_at', DateTime::now('UTC'));
            $this->fetchTable('ContactMessages')->saveOrFail($message);

            return $message;
        });
    }

    /**
     * @param \App\Model\Entity\ContactMessage $message Enquiry.
     * @param string $toStatus Next status.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\ContactMessage
     */
    public function changeStatus(ContactMessage $message, string $toStatus, int $actorUserId): ContactMessage
    {
        $current = (string)($message->status ?: ContactMessage::STATUS_NEW);
        $allowed = ContactMessage::nextStatuses($current);
        if (!in_array($toStatus, $allowed, true)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot move an enquiry from %s to %s.',
                $current,
                $toStatus,
            ));
        }

        $message->set('status', $toStatus);
        $this->fetchTable('ContactMessages')->saveOrFail($message);
        $this->recordEvent(
            $message,
            $actorUserId,
            'system',
            'internal',
            'Status set to ' . (ContactMessage::statusLabels()[$toStatus] ?? $toStatus),
        );

        return $message;
    }

    /**
     * @param \App\Model\Entity\ContactMessage $message Enquiry.
     * @param int|null $userId Staff user id, or null to clear.
     * @param int $actorUserId Acting staff user.
     * @return \App\Model\Entity\ContactMessage
     */
    public function assign(ContactMessage $message, ?int $userId, int $actorUserId): ContactMessage
    {
        if ($userId !== null && $userId > 0) {
            $this->fetchTable('Users')->get($userId);
        } else {
            $userId = null;
        }
        $message->set('assigned_to_user_id', $userId);
        $this->fetchTable('ContactMessages')->saveOrFail($message);
        $this->recordEvent(
            $message,
            $actorUserId,
            'system',
            'internal',
            $userId ? 'Assigned to user #' . $userId : 'Cleared assignment',
        );

        return $message;
    }

    /**
     * @param \App\Model\Entity\ContactMessage $message Enquiry.
     * @param int $actorUserId Acting staff user.
     * @param string $channel Channel.
     * @param string $direction inbound|outbound|internal.
     * @param string $body Event body.
     * @return void
     */
    private function recordEvent(
        ContactMessage $message,
        int $actorUserId,
        string $channel,
        string $direction,
        string $body,
    ): void {
        $events = $this->fetchTable('ContactMessageEvents');
        $event = $events->newEmptyEntity();
        $event->contact_message_id = $message->id;
        $event->actor_user_id = $actorUserId;
        $event->channel = $channel;
        $event->direction = $direction;
        $event->body = $body;
        $events->saveOrFail($event);
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('ContactMessages')->getConnection();
    }
}
