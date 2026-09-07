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

        $this->setTable('Customer');
        $this->setPrimaryKey('CustomerId');
        $this->setDisplayField('CustomerName');

        $this->hasMany('Orders', [
            'foreignKey' => 'CustomerId',
        ]);

        $this->hasMany('Quotations', [
            'foreignKey' => 'CustomerId',
        ]);
    }

    public function findOrCreateFromContact(array $data)
    {
        $email = (string)($data['Email'] ?? $data['email'] ?? '');

        $customer = $this->find()
            ->where(['Email' => $email])
            ->first();

        $payload = [
            'CustomerName' => (string)($data['CustomerName'] ?? $data['name'] ?? ''),
            'Email' => $email,
            'Address' => (string)($data['Address'] ?? $data['address'] ?? ''),
            'Phone' => (string)($data['Phone'] ?? $data['phone'] ?? ''),
        ];

        if ($customer) {
            $customer = $this->patchEntity($customer, $payload);

            return $this->saveOrFail($customer);
        }

        $customer = $this->newEntity($payload);

        return $this->saveOrFail($customer);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->notEmptyString('CustomerName');
        $validator->email('Email')->notEmptyString('Email');
        $validator->allowEmptyString('Phone');
        $validator->allowEmptyString('Address');

        return $validator;
    }
}
