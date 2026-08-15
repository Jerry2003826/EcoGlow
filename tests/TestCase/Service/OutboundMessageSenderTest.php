<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\Outbound\OutboundMessageSender;
use Cake\I18n\DateTime;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

/**
 * Outbound mail consumer: send, retry, and do not send twice.
 */
class OutboundMessageSenderTest extends TestCase
{
    use EmailTrait;

    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Customers',
        'app.OutboundMessageEvents',
        'app.OutboundMessages',
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->fetchTable('OutboundMessageEvents')->deleteAll(['id >' => 0]);
        $this->fetchTable('OutboundMessages')->deleteAll(['id >' => 0]);
    }

    /**
     * A queued contact reply is rendered and marked sent.
     *
     * @return void
     */
    public function testSendsContactReplyOnce(): void
    {
        $id = $this->queueMessage([
            'reference_number' => 'OM-TEST-1',
            'recipient' => 'alex@example.com',
            'template_key' => 'contact_reply',
            'subject' => 'Re: Pendant height',
            'body_text' => 'The Marlow sits 180 cm to the shade.',
        ]);

        $sender = new OutboundMessageSender();
        $first = $sender->process(10);
        $this->assertSame(1, $first['sent']);
        $this->assertMailCount(1);
        $this->assertMailSentTo('alex@example.com');
        $this->assertMailSubjectContains('Re: Pendant height');
        $this->assertMailContains('The Marlow sits 180 cm to the shade.');

        $row = $this->fetchTable('OutboundMessages')->get($id);
        $this->assertSame('sent', $row->status);
        $this->assertSame(0, (int)$row->attempt_count);

        $second = $sender->process(10);
        $this->assertSame(0, $second['sent']);
        $this->assertMailCount(1, 'A sent row must not be delivered again.');
        $this->assertSame('sent', $this->fetchTable('OutboundMessages')->get($id)->status);
    }

    /**
     * Failed deliveries increment attempt_count and stop after the cap.
     *
     * @return void
     */
    public function testRetriesThenMarksFailed(): void
    {
        $id = $this->queueMessage([
            'reference_number' => 'OM-TEST-FAIL',
            'recipient' => 'not-a-valid-email',
            'template_key' => 'contact_reply',
            'subject' => 'Will fail',
            'body_text' => 'Should not send.',
        ]);

        $sender = new OutboundMessageSender();
        $retried = 0;
        $failed = 0;
        for ($i = 0; $i < OutboundMessageSender::MAX_ATTEMPTS; $i++) {
            $stats = $sender->processOne($id);
            if ($stats === 'retried') {
                $retried++;
            }
            if ($stats === 'failed') {
                $failed++;
            }
        }

        $this->assertSame(OutboundMessageSender::MAX_ATTEMPTS - 1, $retried);
        $this->assertSame(1, $failed);

        $row = $this->fetchTable('OutboundMessages')->get($id);
        $this->assertSame('failed', $row->status);
        $this->assertSame(OutboundMessageSender::MAX_ATTEMPTS, (int)$row->attempt_count);

        $this->assertSame('skipped', $sender->processOne($id));
        $this->assertMailCount(0);
    }

    /**
     * A stale sending row that SMTP already accepted must be marked sent, not resent.
     *
     * @return void
     */
    public function testReclaimAfterSmtpAcceptedDoesNotResend(): void
    {
        $id = $this->queueMessage([
            'reference_number' => 'OM-TEST-SMTP',
            'recipient' => 'casey@example.com',
            'template_key' => 'contact_reply',
            'subject' => 'Already handed to SMTP',
            'body_text' => 'Do not send twice.',
        ]);
        $messages = $this->fetchTable('OutboundMessages');
        $messages->getConnection()->execute(
            "UPDATE outbound_messages
                SET status = 'sending', modified = DATE_SUB(UTC_TIMESTAMP(6), INTERVAL 20 MINUTE)
              WHERE id = ?",
            [$id],
        );

        $events = $this->fetchTable('OutboundMessageEvents');
        $event = $events->newEmptyEntity();
        $event->set('outbound_message_id', $id);
        $event->set('event_type', 'smtp_accepted');
        $event->set('payload', ['recipient' => 'casey@example.com']);
        $event->set('occurred_at', DateTime::now('UTC'));
        $events->saveOrFail($event);

        $sender = new OutboundMessageSender();
        $stats = $sender->process(10);
        $this->assertSame(0, $stats['sent']);
        $this->assertMailCount(0);
        $this->assertSame('sent', (string)$messages->get($id)->status);
    }

    /**
     * @param array<string, mixed> $fields Queue fields.
     * @return int
     */
    private function queueMessage(array $fields): int
    {
        $table = $this->fetchTable('OutboundMessages');
        $message = $table->newEmptyEntity();
        $message->set('reference_number', $fields['reference_number']);
        $message->set('channel', 'email');
        $message->set('recipient', $fields['recipient']);
        $message->set('template_key', $fields['template_key']);
        $message->set('subject', $fields['subject']);
        $message->set('body_text', $fields['body_text']);
        $message->set('status', 'queued');
        $message->set('metadata', []);
        $message->set('attempt_count', 0);
        $table->saveOrFail($message);

        return (int)$message->id;
    }
}
