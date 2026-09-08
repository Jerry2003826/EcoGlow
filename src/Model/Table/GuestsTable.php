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
            ->requirePresence('GuestName')
            ->notEmptyString('GuestName');

        $validator
            ->email('Email')
            ->requirePresence('Email')
            ->notEmptyString('Email');

        $validator
            ->requirePresence('Address')
            ->notEmptyString('Address');

        $validator
            ->requirePresence('Phone')
            ->notEmptyString('Phone')
            ->add('Phone', 'digits', [
                'rule' => static function ($value): bool {
                    return (bool)preg_match('/^[0-9]+$/', (string)$value);
                },
            ]);

        return $validator;
    }
}
