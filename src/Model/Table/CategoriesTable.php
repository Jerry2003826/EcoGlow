<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * Categories Model
 */
class CategoriesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('categories');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');
        $this->hasMany('Products', ['foreignKey' => 'category_id']);
    }
}
