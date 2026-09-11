<?php
// src/Model/Table/StaffAccessesTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class StaffAccessesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('StaffAccess');
        $this->setPrimaryKey('AccessId');
        $this->setDisplayField('PermissionKey');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('PermissionKey')
            ->maxLength('PermissionKey', 100)
            ->requirePresence('PermissionKey');

        $validator
            ->scalar('ModuleLabel')
            ->maxLength('ModuleLabel', 150)
            ->requirePresence('ModuleLabel');

        return $validator;
    }
}
