<?php
declare(strict_types=1);

use App\Service\Security\SeedPassword;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

/**
 * UsersSeed class.
 */
class UsersSeed extends BaseSeed
{
    /**
     * Seeds the default administrator account for the admin area.
     * ADMIN_SEED_PASSWORD must be a non-placeholder value of at least 20 characters.
     *
     * @return void
     */
    public function run(): void
    {
        $email = 'admin@ecoglow.local';
        $quoted = $this->getAdapter()->getConnection()->quote($email);
        $existing = $this->fetchRow('SELECT id FROM users WHERE email = ' . $quoted);
        if ($existing) {
            return;
        }

        $hasher = new DefaultPasswordHasher();
        $password = SeedPassword::require('ADMIN_SEED_PASSWORD');
        $now = date('Y-m-d H:i:s');

        $this->table('users')->insert([
            [
                'email' => $email,
                'password' => $hasher->hash($password),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'role' => 'admin',
                'status' => 'active',
                'auth_version' => 1,
                'email_verified_at' => $now,
                'created' => $now,
                'modified' => $now,
            ],
        ])->save();
    }
}
