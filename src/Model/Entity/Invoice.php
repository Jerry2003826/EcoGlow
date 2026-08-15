<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property int $id
 * @property string $invoice_number
 * @property int|null $sales_order_id
 * @property int|null $customer_id
 * @property string $status
 * @property \Cake\I18n\Date|null $issue_date
 * @property \Cake\I18n\Date|null $due_date
 * @property int $subtotal_cents
 * @property int $discount_cents
 * @property int $shipping_cents
 * @property int $tax_cents
 * @property int $grand_total_cents
 * @property int $amount_paid_cents
 * @property int $credit_applied_cents
 * @property int $balance_due_cents
 * @property array|null $business_snapshot
 * @property array|null $customer_snapshot
 * @property array<\App\Model\Entity\InvoiceItem> $invoice_items
 * @property \App\Model\Entity\Customer|null $customer
 * @property \App\Model\Entity\SalesOrder|null $sales_order
 */
class Invoice extends Entity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PAID = 'paid';
    public const STATUS_CREDITED = 'credited';
    public const STATUS_VOID = 'void';
    public const STATUS_OVERDUE = 'overdue';

    /**
     * Amounts, status and snapshots are set by InvoiceService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CREDITED => 'Credited',
            self::STATUS_VOID => 'Void',
            self::STATUS_OVERDUE => 'Overdue',
        ];
    }

    /**
     * @param string $status Status key.
     * @return string
     */
    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_PAID, self::STATUS_CREDITED => 'success',
            self::STATUS_ISSUED, self::STATUS_OVERDUE => 'warning',
            self::STATUS_VOID => 'error',
            default => 'muted',
        };
    }

    /**
     * Outstanding issued invoices past their due date.
     *
     * @param \Cake\I18n\Date $today Melbourne business date.
     * @return bool
     */
    public function isOverdue(Date $today): bool
    {
        if ($this->due_date === null) {
            return false;
        }
        if (in_array($this->status, [self::STATUS_PAID, self::STATUS_CREDITED, self::STATUS_VOID], true)) {
            return false;
        }
        if ((int)$this->balance_due_cents <= 0) {
            return false;
        }

        return $this->due_date->lessThan($today);
    }
}
