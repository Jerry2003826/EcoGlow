<?php
declare(strict_types=1);

namespace App\Service\Security;

use Cake\Http\Session;

/**
 * Session keys that must not survive logout or account switch.
 */
final class SensitiveSession
{
    public const MFA_SETUP = 'MfaSetup';

    public const CHECKOUT_ATTEMPT = 'Checkout.attempt_id';

    /**
     * @param \Cake\Http\Session $session Session.
     * @return void
     */
    public static function clear(Session $session): void
    {
        $session->delete('Auth');
        $session->delete('AuthV2');
        $session->delete('AuthVersion');
        $session->delete('MfaVerified');
        $session->delete(self::MFA_SETUP);
        $session->delete(self::CHECKOUT_ATTEMPT);
    }
}
