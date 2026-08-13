<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Feature flags used by checkout and bookings.
 */
class FeatureFlagsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'flag_key' => 'commerce.online_payments',
                'enabled' => 1,
                'rollout_percentage' => 100,
                'rules' => [],
                'description' => 'Full-amount Stripe payments on web checkout',
                'updated_by_user_id' => null,
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'flag_key' => 'services.installation_repairs',
                'enabled' => 1,
                'rollout_percentage' => 100,
                'rules' => [],
                'description' => 'Installation and repair bookings',
                'updated_by_user_id' => null,
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'flag_key' => 'commerce.customer_account_required',
                'enabled' => 1,
                'rollout_percentage' => 100,
                'rules' => [],
                'description' => 'Checkout requires a customer account',
                'updated_by_user_id' => null,
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
