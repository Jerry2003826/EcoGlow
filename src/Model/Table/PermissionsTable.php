<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PermissionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('permissions');
        $this->setPrimaryKey('id');

        $this->hasMany('RolePermissions', [
            'foreignKey' => 'permission_id',
        ]);

        $this->hasMany('UserPermissionOverrides', [
            'foreignKey' => 'permission_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('permission_key')
            ->maxLength('permission_key', 150)
            ->requirePresence('permission_key', 'create')
            ->notEmptyString('permission_key');

        return $validator;
    }
}
