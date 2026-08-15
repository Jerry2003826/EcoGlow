<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

/**
 * Session login helper for tests that cannot extend AdminAppTestCase.
 */
trait AdminAuthTrait
{
    /**
     * Log in a fixture user via the session.
     *
     * @param int $userId UsersFixture id.
     * @return void
     */
    protected function loginAs(int $userId): void
    {
        $user = $this->fetchTable('Users')->get($userId);
        $this->session([
            'AuthV2' => $userId,
            'AuthVersion' => (int)($user->get('auth_version') ?: 1),
        ]);
    }
}
