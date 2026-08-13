<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * OutboundMessageEvents Model
 *
 * @method \App\Model\Entity\OutboundMessageEvent newEmptyEntity()
 * @method \App\Model\Entity\OutboundMessageEvent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class OutboundMessageEventsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('outbound_message_events');
        $this->setPrimaryKey('id');
        $this->belongsTo('OutboundMessages', ['foreignKey' => 'outbound_message_id']);
        $this->mapJsonColumns(['payload']);
    }
}
