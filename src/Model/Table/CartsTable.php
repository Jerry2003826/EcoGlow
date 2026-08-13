<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Carts Model
 *
 * @method \App\Model\Entity\Cart newEmptyEntity()
 * @method \App\Model\Entity\Cart get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Cart saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class CartsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('carts');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);
        $this->hasMany('CartItems', [
            'foreignKey' => 'cart_id',
            'sort' => ['CartItems.id' => 'ASC'],
        ]);
    }
}
