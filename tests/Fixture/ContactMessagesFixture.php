<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactMessagesFixture
 */
class ContactMessagesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Jane Citizen',
                'email' => 'jane@example.com',
                'phone' => '0400000001',
                'subject' => 'Smart bulb enquiry',
                'message' => 'Do your smart bulbs work with Google Home?',
                'is_read' => false,
                'created' => '2026-08-05 10:00:00',
            ],
            [
                'id' => 2,
                'name' => 'John Smith',
                'email' => 'john@example.com',
                'phone' => null,
                'subject' => 'Installation quote',
                'message' => 'I would like a quote for installing six LED ceiling lights.',
                'is_read' => true,
                'created' => '2026-08-04 09:00:00',
            ],
        ];
        parent::init();
    }
}
