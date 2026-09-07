<?php
// src/Model/Table/GuestsTable.php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class GuestsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('Guest');
        $this->setPrimaryKey('GuestId');
        $this->setDisplayField('GuestName');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('GuestName', 'create')
            ->notEmptyString('GuestName');

        $validator
            ->email('Email')
            ->requirePresence('Email', 'create')
            ->notEmptyString('Email');

        return $validator;
    }
}
