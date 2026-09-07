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

        $this->setTable('suppliers');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

        $this->belongsToMany('Products', [
            'joinTable' => 'product_suppliers',
            'foreignKey' => 'supplier_id',
            'targetForeignKey' => 'product_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('code')->maxLength('code', 40);
        $validator->requirePresence('code', 'create');
        $validator->notEmptyString('code');

        $validator->scalar('name')->maxLength('name', 200);
        $validator->requirePresence('name', 'create');
        $validator->notEmptyString('name');

        $validator->allowEmptyString('email')->email('email');
        $validator->allowEmptyString('phone');
        $validator->allowEmptyString('notes');
        $validator->allowEmptyString('address_line_1');
        $validator->allowEmptyString('suburb');
        $validator->allowEmptyString('state');
        $validator->allowEmptyString('postcode');

        return $validator;
    }
}
