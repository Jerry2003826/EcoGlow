<?php
declare(strict_types=1);

use App\Service\Security\SeedPassword;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

/**
 * Demo storefront customer for local one-click setup.
 */
class DemoCustomerSeed extends BaseSeed
{
    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return [
            'UsersSeed',
        ];
    }

    /**
     * Create customer@ecoglow.local if it is missing.
     *
     * @return void
     */
    public function run(): void
    {
        $email = 'customer@ecoglow.local';
        $quoted = $this->getAdapter()->getConnection()->quote($email);
        $existing = $this->fetchRow('SELECT id FROM users WHERE email = ' . $quoted);
        if ($existing) {
            return;
        }

        $hasher = new DefaultPasswordHasher();
        $password = SeedPassword::require('CUSTOMER_SEED_PASSWORD');
        $now = date('Y-m-d H:i:s');

        $this->table('users')->insert([
            [
                'email' => $email,
                'password' => $hasher->hash($password),
                'first_name' => 'Demo',
                'last_name' => 'Customer',
                'phone' => '0400000000',
                'role' => 'customer',
                'status' => 'active',
                'auth_version' => 1,
                'email_verified_at' => $now,
                'created' => $now,
                'modified' => $now,
            ],
        ])->save();

        $user = $this->fetchRow('SELECT id FROM users WHERE email = ' . $quoted);
        if (!$user || !isset($user['id'])) {
            throw new RuntimeException('Demo customer user could not be created.');
        }

        $this->table('customers')->insert([
            [
                'user_id' => $user['id'],
                'email' => $email,
                'phone' => '0400000000',
                'first_name' => 'Demo',
                'last_name' => 'Customer',
                'status' => 'active',
                'source' => 'web',
                'created' => $now,
                'modified' => $now,
            ],
        ])->save();
    }
}
