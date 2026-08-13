<?php
declare(strict_types=1);

namespace App\Service\Services;

use App\Model\Entity\ServiceAppointment;
use App\Model\Entity\ServiceRequest;
use Cake\Datasource\ConnectionInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;

/**
 * Staff scheduling with overlap protection for a technician's diary.
 */
class AppointmentService
{
    use LocatorAwareTrait;

    /**
     * Confirm a request and insert a non-overlapping appointment.
     *
     * Locks the technician row, then the overlapping appointment range,
     * inside one transaction. MariaDB has no exclusion constraint.
     *
     * @param \App\Model\Entity\ServiceRequest $request Request.
     * @param int $staffUserId Technician user id.
     * @param \Cake\I18n\DateTime $startsAt Start (UTC).
     * @param \Cake\I18n\DateTime $endsAt End (UTC).
     * @param int $actorUserId Acting staff user.
     * @param string|null $instructions Customer-facing notes.
     * @return \App\Model\Entity\ServiceAppointment
     */
    public function schedule(
        ServiceRequest $request,
        int $staffUserId,
        DateTime $startsAt,
        DateTime $endsAt,
        int $actorUserId,
        ?string $instructions = null,
    ): ServiceAppointment {
        if ($endsAt->lessThanOrEquals($startsAt)) {
            throw new InvalidArgumentException('The appointment must end after it starts.');
        }
        $this->fetchTable('Users')->get($staffUserId);

        $lockName = 'svc_appt_staff_' . $staffUserId;

        return $this->connection()->transactional(function () use (
            $request,
            $staffUserId,
            $startsAt,
            $endsAt,
            $actorUserId,
            $instructions,
            $lockName,
        ) {
            $lockRow = $this->connection()->execute(
                'SELECT GET_LOCK(?, 2) AS locked',
                [$lockName],
            )->fetch('assoc');
            if ((int)($lockRow['locked'] ?? 0) !== 1) {
                throw new InvalidArgumentException(
                    'That technician is being scheduled by someone else. Please try again.',
                );
            }
            try {
                $this->connection()->execute(
                    'SELECT id FROM users WHERE id = ? FOR UPDATE',
                    [$staffUserId],
                    ['integer'],
                );
                $this->assertNoOverlap($staffUserId, $startsAt, $endsAt, null);

                $appointments = $this->fetchTable('ServiceAppointments');
                $appointment = $appointments->newEmptyEntity();
                $appointment->set('service_request_id', $request->id);
                $appointment->set('assigned_staff_user_id', $staffUserId);
                $appointment->set('starts_at', $startsAt);
                $appointment->set('ends_at', $endsAt);
                $appointment->set('status', ServiceAppointment::STATUS_CONFIRMED);
                $appointment->set('customer_instructions', $instructions);
                $appointment->set('created_by_user_id', $actorUserId);
                $appointments->saveOrFail($appointment);

                $request->set('status', ServiceRequest::STATUS_SCHEDULED);
                $request->set('assigned_staff_user_id', $staffUserId);
                $this->fetchTable('ServiceRequests')->saveOrFail($request);

                return $appointment;
            } finally {
                $this->connection()->execute('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        });
    }

    /**
     * @param \App\Model\Entity\ServiceRequest $request Request.
     * @param string $status Next request status.
     * @return \App\Model\Entity\ServiceRequest
     */
    public function updateRequestStatus(ServiceRequest $request, string $status): ServiceRequest
    {
        $allowed = ServiceRequest::statusLabels();
        if (!isset($allowed[$status])) {
            throw new InvalidArgumentException('That status is not valid.');
        }
        $request->set('status', $status);
        if ($status === ServiceRequest::STATUS_COMPLETED) {
            $request->set('completed_at', DateTime::now('UTC'));
        }
        $this->fetchTable('ServiceRequests')->saveOrFail($request);

        $map = [
            ServiceRequest::STATUS_IN_PROGRESS => ServiceAppointment::STATUS_IN_PROGRESS,
            ServiceRequest::STATUS_COMPLETED => ServiceAppointment::STATUS_COMPLETED,
            ServiceRequest::STATUS_CANCELLED => ServiceAppointment::STATUS_CANCELLED,
        ];
        if (isset($map[$status])) {
            $this->fetchTable('ServiceAppointments')->updateAll(
                ['status' => $map[$status]],
                [
                    'service_request_id' => $request->id,
                    'status IN' => ServiceAppointment::blockingStatuses(),
                ],
            );
        }

        return $request;
    }

    /**
     * @param \App\Model\Entity\ServiceRequest $request Request.
     * @param int $staffUserId Technician.
     * @param string $summary Work summary.
     * @param int $minutes Duration.
     * @param int|null $appointmentId Optional appointment.
     * @return void
     */
    public function addWorkLog(
        ServiceRequest $request,
        int $staffUserId,
        string $summary,
        int $minutes,
        ?int $appointmentId,
    ): void {
        $summary = trim($summary);
        if ($summary === '' || $minutes < 1) {
            throw new InvalidArgumentException('Enter a work summary and a duration of at least 1 minute.');
        }
        $logs = $this->fetchTable('ServiceWorkLogs');
        $log = $logs->newEmptyEntity();
        $log->set('service_request_id', $request->id);
        $log->set('appointment_id', $appointmentId);
        $log->set('staff_user_id', $staffUserId);
        $log->set('started_at', DateTime::now('UTC')->subMinutes($minutes));
        $log->set('ended_at', DateTime::now('UTC'));
        $log->set('duration_minutes', $minutes);
        $log->set('work_summary', $summary);
        $log->set('billable', true);
        $logs->saveOrFail($log);
    }

    /**
     * @param \App\Model\Entity\ServiceRequest $request Request.
     * @param int $variantId Product variant used on site.
     * @param int $quantity Quantity.
     * @param int $actorUserId Acting staff.
     * @return void
     */
    public function addPart(ServiceRequest $request, int $variantId, int $quantity, int $actorUserId): void
    {
        if ($variantId < 1 || $quantity < 1) {
            throw new InvalidArgumentException('Choose a part and a quantity of at least 1.');
        }
        $variant = $this->fetchTable('ProductVariants')->get($variantId);
        $parts = $this->fetchTable('ServicePartsUsed');
        $part = $parts->newEmptyEntity();
        $part->set('service_request_id', $request->id);
        $part->set('product_variant_id', $variantId);
        $part->set('quantity', $quantity);
        $part->set('unit_cost_snapshot_cents', $variant->cost_cents);
        $part->set('unit_charge_snapshot_cents', $variant->price_cents);
        $part->set('created_by_user_id', $actorUserId);
        $parts->saveOrFail($part);
    }

    /**
     * @param int $staffUserId Technician.
     * @param \Cake\I18n\DateTime $startsAt New start.
     * @param \Cake\I18n\DateTime $endsAt New end.
     * @param int|null $ignoreId Appointment to ignore when rescheduling.
     * @return void
     */
    public function assertNoOverlap(
        int $staffUserId,
        DateTime $startsAt,
        DateTime $endsAt,
        ?int $ignoreId,
    ): void {
        $query = $this->fetchTable('ServiceAppointments')->find()
            ->where([
                'assigned_staff_user_id' => $staffUserId,
                'status IN' => ServiceAppointment::blockingStatuses(),
                'starts_at <' => $endsAt,
                'ends_at >' => $startsAt,
            ]);
        if ($ignoreId !== null) {
            $query->andWhere(['id !=' => $ignoreId]);
        }
        if ($query->count() > 0) {
            throw new InvalidArgumentException(
                'That technician already has an appointment in this time window.',
            );
        }
    }

    /**
     * @return \Cake\Datasource\ConnectionInterface
     */
    private function connection(): ConnectionInterface
    {
        return $this->fetchTable('ServiceAppointments')->getConnection();
    }
}
