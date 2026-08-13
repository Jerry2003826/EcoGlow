<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ServiceWorkLogs Model
 *
 * @method \App\Model\Entity\ServiceWorkLog newEmptyEntity()
 * @method \App\Model\Entity\ServiceWorkLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ServiceWorkLogsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_work_logs');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('ServiceRequests', ['foreignKey' => 'service_request_id']);
        $this->belongsTo('ServiceAppointments', ['foreignKey' => 'appointment_id']);
        $this->belongsTo('Users', ['foreignKey' => 'staff_user_id']);
        $this->hasMany('ServicePartsUsed', ['foreignKey' => 'service_work_log_id']);
    }
}
