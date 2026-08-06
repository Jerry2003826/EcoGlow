<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

/**
 * UsersSeed class.
 */
class UsersSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Seeds the default administrator account for the admin area.
     * Override the password via the ADMIN_SEED_PASSWORD environment variable.
     *
     * @return void
     */
    public function run(): void
    {
        $email = 'admin@ecoglow.local';

        // `users.email` is unique, so bail out if the admin already exists to
        // keep this seed safe to re-run.
        $existing = $this->fetchRow(
            sprintf("SELECT id FROM users WHERE email = '%s'", $email),
        );
        if ($existing) {
            return;
        }

        $hasher = new DefaultPasswordHasher();
        $password = env('ADMIN_SEED_PASSWORD') ?: 'admin123';

        $data = [
            [
                'email' => $email,
                'password' => $hasher->hash($password),
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('users');
        $table->insert($data)->save();
    }
}
