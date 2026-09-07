<?php
// src/Model/Table/UsersTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('User');
        $this->setPrimaryKey('UserId');
        $this->setDisplayField('Email');
        $this->hasOne('Customers', ['foreignKey' => 'UserId']);
        $this->hasOne('Staff', ['foreignKey' => 'UserId']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->email('Email');

        return $validator;
    }

    public function findActiveAuth(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'Status' => 'active',
        ]);
    }
}
