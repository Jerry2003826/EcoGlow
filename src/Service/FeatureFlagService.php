<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Reads feature_flags.enabled. Missing rows use the supplied default.
 */
class FeatureFlagService
{
    use LocatorAwareTrait;

    public const ONLINE_PAYMENTS = 'commerce.online_payments';

    public const CUSTOMER_ACCOUNT_REQUIRED = 'commerce.customer_account_required';

    public const INSTALLATION_REPAIRS = 'services.installation_repairs';

    /**
     * @param string $flagKey feature_flags.flag_key
     * @param bool $default Value when the row is missing.
     * @return bool
     */
    public function enabled(string $flagKey, bool $default = false): bool
    {
        try {
            $row = $this->fetchTable('FeatureFlags')->find()
                ->where(['flag_key' => $flagKey])
                ->first();
        } catch (Throwable) {
            return $default;
        }
        if ($row === null) {
            return $default;
        }

        return (bool)$row->get('enabled');
    }
}
