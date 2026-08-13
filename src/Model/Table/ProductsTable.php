<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Products Model
 */
class ProductsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('products');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Categories', ['foreignKey' => 'category_id']);
        $this->hasMany('ProductVariants', ['foreignKey' => 'product_id']);
        $this->hasMany('ProductImages', ['foreignKey' => 'product_id']);
        $this->mapJsonColumns(['specifications', 'tags', 'metadata']);
    }
}
