<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Outbound\OutboundMessageSender;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;

/**
 * Consume outbound_messages and send due email.
 *
 * Idempotent: a row is claimed with status queued → sending, so a second
 * process (or a re-run) cannot deliver the same row twice.
 */
class SendOutboundMessagesCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'send_outbound_messages';
    }

    /**
     * @inheritDoc
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription(
            'Send queued outbound_messages. Safe to run from cron every minute.',
        );
        $parser->addOption('limit', [
            'short' => 'l',
            'help' => 'Maximum messages to claim in this run.',
            'default' => '50',
        ]);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $limit = (int)$args->getOption('limit');
        if ($limit < 1) {
            $limit = 50;
        }

        $stats = (new OutboundMessageSender())->process($limit);
        $io->out(sprintf(
            'Outbound mail: %d sent, %d retried, %d failed, %d skipped.',
            $stats['sent'],
            $stats['retried'],
            $stats['failed'],
            $stats['skipped'],
        ));

        return static::CODE_SUCCESS;
    }
}
