<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * UserPermissionOverrides Model
 */
class UserPermissionOverridesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('user_permission_overrides');
        $this->setPrimaryKey('id');
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
        $this->belongsTo('Permissions', ['foreignKey' => 'permission_id']);
    }
}
