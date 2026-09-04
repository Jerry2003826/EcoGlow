<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CustomersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('customers');
        $this->setPrimaryKey('id');
        $this->setDisplayField('email');

        $this->hasMany('SalesOrders', [
            'foreignKey' => 'customer_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->integer('user_id');
        $validator->allowEmptyString('user_id');

        $validator->email('email');
        $validator->allowEmptyString('email');

        $validator->allowEmptyString('phone');
        $validator->allowEmptyString('first_name');
        $validator->allowEmptyString('last_name');
        $validator->allowEmptyString('company');
        $validator->allowEmptyString('status');
        $validator->allowEmptyString('source');

        return $validator;
    }
}
