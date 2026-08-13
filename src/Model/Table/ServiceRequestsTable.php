<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ServiceRequests Model
 *
 * @method \App\Model\Entity\ServiceRequest newEmptyEntity()
 * @method \App\Model\Entity\ServiceRequest get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\ServiceRequest saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ServiceRequestsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_requests');
        $this->setPrimaryKey('id');
        $this->setDisplayField('request_number');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Customers', ['foreignKey' => 'customer_id']);
        $this->belongsTo('ServiceTypes', ['foreignKey' => 'service_type_id']);
        $this->belongsTo('Users', [
            'foreignKey' => 'assigned_staff_user_id',
            'propertyName' => 'assigned_staff',
        ]);
        $this->hasMany('ServiceAppointments', [
            'foreignKey' => 'service_request_id',
            'sort' => ['ServiceAppointments.starts_at' => 'ASC'],
        ]);
        $this->hasMany('ServiceWorkLogs', ['foreignKey' => 'service_request_id']);
        $this->hasMany('ServicePartsUsed', ['foreignKey' => 'service_request_id']);
        $this->mapJsonColumns(['attachment_urls']);
    }
}
