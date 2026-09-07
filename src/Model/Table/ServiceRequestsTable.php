<?php
// src/Model/Table/ServiceRequestsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ServiceRequestsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Service_Request');
        $this->setPrimaryKey('ServiceRequestId');

        $this->belongsTo('Customers', [
            'foreignKey' => 'CustomerId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('RequestType');
        $validator->notEmptyString('Description');

        return $validator;
    }
}
