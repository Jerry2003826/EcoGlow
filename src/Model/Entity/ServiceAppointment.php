<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceAppointment Entity
 *
 * @property int $id
 * @property int $service_request_id
 * @property int $assigned_staff_user_id
 * @property \Cake\I18n\DateTime $starts_at
 * @property \Cake\I18n\DateTime $ends_at
 * @property string $status
 */
class ServiceAppointment extends Entity
{
    public const STATUS_TENTATIVE = 'tentative';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [];

    /**
     * Statuses that occupy a technician's diary.
     *
     * @return array<int, string>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::STATUS_TENTATIVE,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS,
        ];
    }
}
