<?php
declare(strict_types=1);

namespace App\Service\Outbound;

use App\Model\Entity\OutboundMessage;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Claims queued outbound_messages and delivers them exactly once per claim.
 */
class OutboundMessageSender
{
    use LocatorAwareTrait;
    use MailerAwareTrait;

    /**
     * Attempts allowed before the row is marked failed.
     *
     * @var int
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Stale `sending` rows older than this are treated as a failed attempt.
     *
     * @var int
     */
    public const STALE_SENDING_MINUTES = 15;

    /**
     * @param int $limit Maximum rows to claim in one run.
     * @return array{sent: int, failed: int, retried: int, skipped: int}
     */
    public function process(int $limit = 50): array
    {
        $this->reclaimStaleSending();

        $stats = ['sent' => 0, 'failed' => 0, 'retried' => 0, 'skipped' => 0];
        $ids = $this->dueIds($limit);
        foreach ($ids as $id) {
            $result = $this->processOne($id);
            $stats[$result]++;
        }

        return $stats;
    }

    /**
     * Atomically claim and deliver one row.
     *
     * @param int $id outbound_messages.id
     * @return string sent|failed|retried|skipped
     */
    public function processOne(int $id): string
    {
        $claimed = $this->claim($id);
        if ($claimed === null) {
            return 'skipped';
        }

        try {
            $this->deliver($claimed);
        } catch (Throwable $exception) {
            Log::error(sprintf(
                'Outbound message #%d failed: %s',
                $id,
                $exception->getMessage(),
            ));

            return $this->markFailure($claimed, $exception->getMessage());
        }

        try {
            $this->recordEvent((int)$claimed->id, 'smtp_accepted', [
                'recipient' => $claimed->get('recipient'),
            ]);
            $this->markSent($claimed);
        } catch (Throwable $exception) {
            Log::error(sprintf(
                'Outbound message #%d was accepted by SMTP but not marked sent: %s',
                $id,
                $exception->getMessage(),
            ));
        }

        return 'sent';
    }

    /**
     * @param int $limit Max ids.
     * @return list<int>
     */
    private function dueIds(int $limit): array
    {
        $now = DateTime::now('UTC');
        $rows = $this->fetchTable('OutboundMessages')->find()
            ->select(['id'])
            ->where([
                'OutboundMessages.status' => 'queued',
                'OR' => [
                    'OutboundMessages.scheduled_at IS' => null,
                    'OutboundMessages.scheduled_at <=' => $now,
                ],
            ])
            ->orderBy(['OutboundMessages.id' => 'ASC'])
            ->limit($limit)
            ->all();

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int)$row->id;
        }

        return $ids;
    }

    /**
     * Move queued → sending only if the row is still queued.
     *
     * @param int $id Message id.
     * @return \App\Model\Entity\OutboundMessage|null
     */
    private function claim(int $id): ?OutboundMessage
    {
        return $this->connection()->transactional(function () use ($id) {
            $updated = $this->fetchTable('OutboundMessages')->updateAll(
                ['status' => 'sending'],
                ['id' => $id, 'status' => 'queued'],
            );
            if ($updated !== 1) {
                return null;
            }

            return $this->fetchTable('OutboundMessages')->get($id);
        });
    }

    /**
     * @param \App\Model\Entity\OutboundMessage $message Claimed row.
     * @return void
     */
    private function deliver(OutboundMessage $message): void
    {
        $template = (string)($message->get('template_key') ?: 'contact_reply');
        $method = match ($template) {
            'invoice' => 'invoice',
            'order_confirmation' => 'orderConfirmation',
            default => 'contactReply',
        };
        $this->getMailer('Outbound')->send($method, [$message]);
    }

    /**
     * @param \App\Model\Entity\OutboundMessage $message Claimed row.
     * @return void
     */
    private function markSent(OutboundMessage $message): void
    {
        $this->completeAcceptedSend((int)$message->id, (string)$message->get('recipient'));
    }

    /**
     * Persist sent after SMTP accepted the message. Safe to call again.
     *
     * @param int $id Message id.
     * @param string|null $recipient Recipient for the sent event.
     * @return void
     */
    private function completeAcceptedSend(int $id, ?string $recipient = null): void
    {
        $now = DateTime::now('UTC');
        $updated = $this->fetchTable('OutboundMessages')->updateAll(
            [
                'status' => 'sent',
                'sent_at' => $now,
                'failed_at' => null,
                'failure_reason' => null,
            ],
            [
                'id' => $id,
                'status IN' => ['sending', 'queued'],
            ],
        );
        if ($updated !== 1) {
            return;
        }
        if (
            $this->fetchTable('OutboundMessageEvents')->exists([
                'outbound_message_id' => $id,
                'event_type' => 'sent',
            ])
        ) {
            return;
        }
        $this->recordEvent($id, 'sent', [
            'recipient' => $recipient,
        ]);
    }

    /**
     * @param \App\Model\Entity\OutboundMessage $message Claimed row.
     * @param string $reason Failure text.
     * @return string failed|retried
     */
    private function markFailure(OutboundMessage $message, string $reason): string
    {
        $attempts = (int)$message->get('attempt_count') + 1;
        $message->set('attempt_count', $attempts);
        $message->set('failure_reason', $reason);
        $this->recordEvent((int)$message->id, 'failed', [
            'attempt' => $attempts,
            'reason' => $reason,
        ]);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $message->set('status', 'failed');
            $message->set('failed_at', DateTime::now('UTC'));
            $this->fetchTable('OutboundMessages')->saveOrFail($message);

            return 'failed';
        }

        $message->set('status', 'queued');
        $this->fetchTable('OutboundMessages')->saveOrFail($message);

        return 'retried';
    }

    /**
     * @return void
     */
    private function reclaimStaleSending(): void
    {
        $cutoff = DateTime::now('UTC')->subMinutes(self::STALE_SENDING_MINUTES);
        $stale = $this->fetchTable('OutboundMessages')->find()
            ->where([
                'OutboundMessages.status' => 'sending',
                'OutboundMessages.modified <' => $cutoff,
            ])
            ->all();

        foreach ($stale as $message) {
            $staleId = (int)$message->get('id');
            if ($this->alreadyAcceptedBySmtp($staleId)) {
                $this->completeAcceptedSend($staleId);
                continue;
            }
            $this->markFailure($message, 'Timed out while sending; reclaimed for retry.');
        }
    }

    /**
     * SMTP already accepted this row; reclaim must not send again.
     *
     * @param int $id Message id.
     * @return bool
     */
    private function alreadyAcceptedBySmtp(int $id): bool
    {
        return $this->fetchTable('OutboundMessageEvents')->exists([
            'outbound_message_id' => $id,
            'event_type IN' => ['sent', 'smtp_accepted'],
        ]);
    }

    /**
     * @param int $messageId Queue row id.
     * @param string $type Event type.
     * @param array<string, mixed> $payload Event payload.
     * @return void
     */
    private function recordEvent(int $messageId, string $type, array $payload): void
    {
        $events = $this->fetchTable('OutboundMessageEvents');
        $event = $events->newEmptyEntity();
        $event->set('outbound_message_id', $messageId);
        $event->set('event_type', $type);
        $event->set('payload', $payload);
        $event->set('occurred_at', DateTime::now('UTC'));
        $events->saveOrFail($event);
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('OutboundMessages')->getConnection();
    }
}
