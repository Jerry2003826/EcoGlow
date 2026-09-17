<?php
// src/Model/Table/QuotationsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class QuotationsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Quotation');
        $this->setPrimaryKey('QuotationId');

        $this->belongsTo('Customers', [
            'foreignKey' => 'CustomerId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('CustomerId');
        $validator->decimal('TotalAmount')->greaterThanOrEqual('TotalAmount', 0);

        return $validator;
    }
}
