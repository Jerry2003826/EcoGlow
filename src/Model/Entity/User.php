<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string|null $password_reset_token
 * @property \Cake\I18n\DateTime|null $password_reset_expires
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class User extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * The password reset fields are deliberately absent: they must only ever be
     * written by the reset flow itself (via `set()`), never from request data,
     * otherwise a crafted POST could forge a token or extend its lifetime.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'email' => true,
        'password' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'password',
        'password_reset_token',
        'email_verification_token',
        'pending_email_token',
        'mfa_secret',
    ];

    /**
     * Hash the password automatically when it is set.
     *
     * @param string $password The plain-text password.
     * @return string The hashed password.
     */
    protected function _setPassword(string $password): string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }

        return $password;
    }
}
