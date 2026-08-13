<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * AuditLogs Model
 *
 * @method \App\Model\Entity\AuditLog newEmptyEntity()
 * @method \App\Model\Entity\AuditLog saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class AuditLogsTable extends Table
{
    use JsonColumnsTrait;

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('audit_logs');
        $this->setPrimaryKey('id');
        $this->mapJsonColumns(['before_data', 'after_data']);
    }
}
