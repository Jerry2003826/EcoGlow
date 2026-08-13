<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * InventoryLocations Model
 *
 * @method \App\Model\Entity\InventoryLocation get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\InventoryLocation saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class InventoryLocationsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('inventory_locations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('InventoryBalances', ['foreignKey' => 'inventory_location_id']);
        $this->mapJsonColumns(['address']);
    }
}
