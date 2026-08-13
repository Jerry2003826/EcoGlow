<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * OutboundMessages Model
 *
 * @method \App\Model\Entity\OutboundMessage newEmptyEntity()
 * @method \App\Model\Entity\OutboundMessage saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class OutboundMessagesTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('outbound_messages');
        $this->setDisplayField('reference_number');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->mapJsonColumns(['metadata']);
    }
}
