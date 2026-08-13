<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * ContactMessageEvents Model
 *
 * @method \App\Model\Entity\ContactMessageEvent newEmptyEntity()
 * @method \App\Model\Entity\ContactMessageEvent saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class ContactMessageEventsTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('contact_message_events');
        $this->setPrimaryKey('id');
        $this->belongsTo('ContactMessages', ['foreignKey' => 'contact_message_id']);
        $this->belongsTo('Users', ['foreignKey' => 'actor_user_id']);
    }
}
