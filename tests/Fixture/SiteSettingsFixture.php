<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SiteSettingsFixture
 */
class SiteSettingsFixture extends TestFixture
{
    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $this->records = [
            [
                'setting_key' => 'shipping.free_threshold_cents',
                'setting_value' => 15000,
                'description' => 'Free delivery threshold in cents',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'setting_key' => 'shipping.standard_flat_rate_cents',
                'setting_value' => 1495,
                'description' => 'Standard flat shipping in cents',
                'modified' => '2026-08-06 00:00:00',
            ],
            [
                'setting_key' => 'tax.gst_rate',
                'setting_value' => 0.1,
                'description' => 'GST rate',
                'modified' => '2026-08-06 00:00:00',
            ],
        ];
        parent::init();
    }
}
