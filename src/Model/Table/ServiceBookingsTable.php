<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ServiceBookingsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('service_bookings');
        $this->setPrimaryKey('id');

        $this->belongsTo('Customers', [
            'foreignKey' => 'customer_id',
            'joinType' => 'LEFT',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('booking_number')->maxLength('booking_number', 190);
        $validator->requirePresence('booking_number', 'create');
        $validator->notEmptyString('booking_number');

        $validator->allowEmptyString('guest_name');
        $validator->allowEmptyString('guest_email');
        $validator->allowEmptyString('guest_phone');

        $validator->scalar('service_type')->maxLength('service_type', 30);
        $validator->notEmptyString('service_type');

        $validator->scalar('status')->maxLength('status', 30);
        $validator->notEmptyString('status');

        $validator->dateTime('scheduled_at');
        $validator->notEmptyDateTime('scheduled_at');

        $validator->allowEmptyString('notes');

        return $validator;
    }
}
