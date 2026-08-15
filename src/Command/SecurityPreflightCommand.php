<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Security\RefundIntegrityPreflight;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use RuntimeException;
use Throwable;

/**
 * Deploy gate: refuse unique refund indexes when historical rows collide.
 */
class SecurityPreflightCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'security_preflight';
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $connection = ConnectionManager::get('default');
            if (!$connection instanceof Connection) {
                throw new RuntimeException('Refund preflight requires a SQL connection.');
            }
            RefundIntegrityPreflight::assert($connection);
        } catch (Throwable $exception) {
            $io->err($exception->getMessage());

            return static::CODE_ERROR;
        }
        $io->out('Refund integrity preflight passed.');

        return static::CODE_SUCCESS;
    }
}
