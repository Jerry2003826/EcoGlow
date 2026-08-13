<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Append-only audit_logs writer.
 */
class AuditLogger
{
    use LocatorAwareTrait;

    /**
     * @param int|null $actorUserId Acting staff user.
     * @param string $action Short verb, e.g. role_permission.grant.
     * @param string $entityType Table or concept name.
     * @param int|null $entityId Row id.
     * @param array<string, mixed>|null $before Previous values.
     * @param array<string, mixed>|null $after New values.
     * @param string|null $ipHash Optional hashed IP.
     * @return void
     */
    public function record(
        ?int $actorUserId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $before,
        ?array $after,
        ?string $ipHash = null,
    ): void {
        $logs = $this->fetchTable('AuditLogs');
        $row = $logs->newEmptyEntity();
        $row->actor_user_id = $actorUserId;
        $row->action = $action;
        $row->entity_type = $entityType;
        $row->entity_id = $entityId;
        $row->before_data = $before;
        $row->after_data = $after;
        $row->ip_hash = $ipHash;
        $row->created = DateTime::now('UTC');
        $logs->saveOrFail($row);
    }
}
