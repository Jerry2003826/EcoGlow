<?php

declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class RolePermissionsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('role_permissions');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
        ]);

        $this->belongsTo('Permissions', [
            'foreignKey' => 'permission_id',
        ]);
    }
}
