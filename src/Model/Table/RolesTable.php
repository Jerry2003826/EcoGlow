<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Roles Model
 *
 * @method \App\Model\Entity\Role get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 */
class RolesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('UserRoles', ['foreignKey' => 'role_id']);
        $this->belongsToMany('Permissions', [
            'through' => 'RolePermissions',
            'foreignKey' => 'role_id',
            'targetForeignKey' => 'permission_id',
        ]);
    }
}
