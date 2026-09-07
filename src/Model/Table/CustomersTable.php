<?php
// src/Model/Table/CustomersTable.php

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

    public function findOrCreateFromContact(array $data)
    {
        $customer = $this->find()
            ->where(['email' => (string)$data['email']])
            ->first();

        if ($customer) {
            $customer->first_name = $data['first_name'] ?? $customer->first_name;
            $customer->phone = $data['phone'] ?? $customer->phone;
            $customer->address_line_1 = $data['address_line_1'] ?? $customer->address_line_1;

            return $this->saveOrFail($customer);
        }

        $customer = $this->newEntity([
            'first_name' => $data['first_name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address_line_1' => $data['address_line_1'] ?? '',
        ]);

        return $this->saveOrFail($customer);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->email('email');
        $validator->allowEmptyString('phone');
        $validator->allowEmptyString('first_name');
        $validator->allowEmptyString('last_name');
        $validator->allowEmptyString('company');
        $validator->allowEmptyString('status');
        $validator->allowEmptyString('source');

        return $validator;
    }
}
