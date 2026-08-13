<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;

/**
 * SalesOrder Entity
 *
 * @property int $id
 * @property string $order_number
 * @property int|null $customer_id
 * @property string|null $guest_name
 * @property string|null $guest_email
 * @property string|null $guest_phone
 * @property string $status
 * @property string $source_channel
 * @property string|null $external_source_reference
 * @property \Cake\I18n\Date|null $promised_delivery_date
 * @property int $subtotal_cents
 * @property int $discount_cents
 * @property int $shipping_cents
 * @property int $tax_cents
 * @property int $grand_total_cents
 * @property \Cake\I18n\DateTime|null $placed_at
 * @property \App\Model\Entity\Customer|null $customer
 * @property array<\App\Model\Entity\SalesOrderItem> $sales_order_items
 * @property array<\App\Model\Entity\OrderStatusHistory> $order_status_history
 * @property array<\App\Model\Entity\OrderNote> $order_notes
 */
class SalesOrder extends Entity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ON_HOLD = 'on_hold';

    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_IN_STORE = 'in_store';
    public const CHANNEL_WEB = 'web';

    /**
     * Amounts, status, foreign keys and timestamps are set by OrderService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];

    /**
     * Labels for the status chips. Text is required so colour is never the
     * only cue.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_DISPATCHED => 'Dispatched',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ON_HOLD => 'On hold',
        ];
    }

    /**
     * Source-channel labels for filters and the order form.
     *
     * @return array<string, string>
     */
    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_PHONE => 'Phone',
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_IN_STORE => 'In store',
            self::CHANNEL_WEB => 'Website',
        ];
    }

    /**
     * Statuses that still need packing or a courier booking.
     *
     * @return array<int, string>
     */
    public static function awaitingDispatchStatuses(): array
    {
        return [
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING,
        ];
    }

    /**
     * Statuses that close the promised-date clock.
     *
     * @return array<int, string>
     */
    public static function closedStatuses(): array
    {
        return [
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Visual tone for a status pill. Always paired with the label text.
     *
     * @param string $status Status key.
     * @return string success|warning|error|muted
     */
    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CONFIRMED, self::STATUS_PROCESSING, self::STATUS_DISPATCHED => 'warning',
            self::STATUS_CANCELLED, self::STATUS_ON_HOLD => 'error',
            default => 'muted',
        };
    }

    /**
     * Whether the promised delivery date has passed while the order is open.
     *
     * @param \Cake\I18n\Date $today Local business date.
     * @return bool
     */
    public function isDeliveryOverdue(Date $today): bool
    {
        if ($this->promised_delivery_date === null) {
            return false;
        }
        if (in_array($this->status, self::closedStatuses(), true)) {
            return false;
        }

        return $this->promised_delivery_date->lessThan($today);
    }

    /**
     * Whole days past the promised date, or 0 when not overdue.
     *
     * @param \Cake\I18n\Date $today Local business date.
     * @return int
     */
    public function overdueDays(Date $today): int
    {
        if (!$this->isDeliveryOverdue($today)) {
            return 0;
        }

        return $today->diffInDays($this->promised_delivery_date);
    }

    /**
     * Customer-facing name for lists.
     *
     * @return string
     */
    protected function _getCustomerLabel(): string
    {
        if ($this->customer !== null) {
            return $this->customer->label;
        }
        if ($this->guest_name) {
            return (string)$this->guest_name;
        }
        if ($this->guest_email) {
            return (string)$this->guest_email;
        }
        if ($this->guest_phone) {
            return (string)$this->guest_phone;
        }

        return 'Walk-in';
    }
}
