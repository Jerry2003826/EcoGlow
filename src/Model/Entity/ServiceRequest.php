<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceRequest Entity
 *
 * @property int $id
 * @property string $request_number
 * @property int|null $customer_id
 * @property int $service_type_id
 * @property string $status
 * @property string $contact_name
 * @property \App\Model\Entity\ServiceType|null $service_type
 * @property array<\App\Model\Entity\ServiceAppointment> $service_appointments
 */
class ServiceRequest extends Entity
{
    public const STATUS_NEW = 'new';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Written by ServiceBookingService / AppointmentService.
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
            self::STATUS_NEW => 'Awaiting confirmation',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }
}
