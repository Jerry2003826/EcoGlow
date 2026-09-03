<?php

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

        $this->setTable('users');
        $this->setPrimaryKey('id');

        $this->hasMany('UserRoles', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('UserPermissionOverrides', [
            'foreignKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        return $validator;
    }

    public function findActiveAuth(
        SelectQuery $query,
        array $options
    ): SelectQuery {
        return $query->where([
            'Users.status' => 'active',
            'Users.deleted IS' => null,
        ]);
    }
}
