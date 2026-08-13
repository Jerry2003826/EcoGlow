<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ProductImages Model
 */
class ProductImagesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('product_images');
        $this->setPrimaryKey('id');
        $this->belongsTo('Products', ['foreignKey' => 'product_id']);
        $this->mapJsonColumns(['metadata']);
    }
}
