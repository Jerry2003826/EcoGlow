<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContactMessage Entity
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property bool $is_read
 * @property \Cake\I18n\DateTime $created
 */
class ContactMessage extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Only the public contact form fields are listed. `is_read` is set by the
     * admin area and `created` by the Timestamp behavior, so leaving either
     * mass assignable would let an anonymous submitter pre-mark their own
     * message as read or backdate it.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        'phone' => true,
        'subject' => true,
        'message' => true,
    ];

    public const STATUS_NEW = 'new';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_SPAM = 'spam';

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_IN_PROGRESS => 'In progress',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_SPAM => 'Spam',
        ];
    }

    /**
     * @param string $status Status key.
     * @return string
     */
    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_RESOLVED, self::STATUS_CLOSED => 'success',
            self::STATUS_NEW, self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_SPAM => 'error',
            default => 'muted',
        };
    }

    /**
     * Forward status moves from the current state.
     *
     * @param string $from Current status.
     * @return array<int, string>
     */
    public static function nextStatuses(string $from): array
    {
        return match ($from) {
            self::STATUS_NEW => [self::STATUS_IN_PROGRESS, self::STATUS_SPAM],
            self::STATUS_IN_PROGRESS => [
                self::STATUS_RESOLVED,
                self::STATUS_CLOSED,
                self::STATUS_SPAM,
            ],
            self::STATUS_RESOLVED => [self::STATUS_CLOSED],
            default => [],
        };
    }
}
