<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Service\Security\TotpService;
use Cake\TestSuite\TestCase;

/**
 * Atomic MFA timestep and recovery-code consumption.
 */
class UsersTableTest extends TestCase
{
    /**
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Timesteps must move forward; equal and earlier values are rejected.
     *
     * @return void
     */
    public function testClaimMfaTimestepIsMonotonic(): void
    {
        $users = $this->fetchTable('Users');
        $this->assertTrue($users->claimMfaTimestep(1, 100));
        $this->assertFalse($users->claimMfaTimestep(1, 100));
        $this->assertFalse($users->claimMfaTimestep(1, 99));
        $this->assertTrue($users->claimMfaTimestep(1, 101));
        $this->assertSame(101, (int)$users->get(1)->get('mfa_last_timestep'));
    }

    /**
     * A recovery code can be used once, even if two callers race.
     *
     * @return void
     */
    public function testRecoveryCodeCanBeConsumedOnce(): void
    {
        $users = $this->fetchTable('Users');
        $users->updateAll(
            ['mfa_recovery_hashes' => TotpService::hashRecoveryCodes(['ABCD-EFGH'])],
            ['id' => 1],
        );
        $this->assertTrue($users->consumeRecoveryCode(1, 'ABCD-EFGH'));
        $this->assertFalse($users->consumeRecoveryCode(1, 'ABCD-EFGH'));
        $this->assertFalse($users->consumeRecoveryCode(1, 'ZZZZ-ZZZZ'));
    }

    /**
     * Recovery hashes stay out of array/JSON serialisation.
     *
     * @return void
     */
    public function testRecoveryHashesAreHidden(): void
    {
        $user = $this->fetchTable('Users')->get(1);
        $user->set('mfa_recovery_hashes', '["hash"]');
        $this->assertArrayNotHasKey('mfa_recovery_hashes', $user->toArray());
        $this->assertArrayNotHasKey('mfa_secret', $user->toArray());
    }
}
