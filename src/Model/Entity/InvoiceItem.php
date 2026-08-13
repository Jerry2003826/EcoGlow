<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoiceItem Entity
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $item_name_snapshot
 * @property int $quantity
 * @property int $unit_price_cents
 * @property int $line_total_cents
 */
class InvoiceItem extends Entity
{
    /**
     * Line snapshots are written by InvoiceService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
