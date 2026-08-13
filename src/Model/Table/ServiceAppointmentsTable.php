<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ServiceAppointments Model
 *
 * @method \App\Model\Entity\ServiceAppointment newEmptyEntity()
 * @method \App\Model\Entity\ServiceAppointment saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ServiceAppointmentsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_appointments');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('ServiceRequests', ['foreignKey' => 'service_request_id']);
        $this->belongsTo('Users', [
            'foreignKey' => 'assigned_staff_user_id',
            'propertyName' => 'staff',
        ]);
        $this->hasMany('ServiceWorkLogs', ['foreignKey' => 'appointment_id']);
    }
}
