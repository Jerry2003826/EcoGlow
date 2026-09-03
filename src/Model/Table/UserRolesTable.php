<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class UserRolesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('user_roles');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
        ]);
    }
}
