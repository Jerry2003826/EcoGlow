<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * RolePermissions Model
 */
class RolePermissionsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('role_permissions');
        $this->setPrimaryKey(['role_id', 'permission_id']);
        $this->belongsTo('Roles', ['foreignKey' => 'role_id']);
        $this->belongsTo('Permissions', ['foreignKey' => 'permission_id']);
    }
}
