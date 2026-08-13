<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoiceStatusHistory Entity
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $to_status
 */
class InvoiceStatusHistory extends Entity
{
    /**
     * History is written by InvoiceService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
