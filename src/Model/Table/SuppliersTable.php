<?php
// src/Model/Table/SuppliersTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SuppliersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Supplier');
        $this->setPrimaryKey('SupplierId');
        $this->setDisplayField('SupplierName');

        $this->hasMany('Products', [
            'foreignKey' => 'SupplierId',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('SupplierName');
        $validator->allowEmptyString('Email')->email('Email');
        $validator->allowEmptyString('Phone');
        $validator->allowEmptyString('Address');

        return $validator;
    }
}
