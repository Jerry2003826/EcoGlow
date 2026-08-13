<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Customer;
use App\Service\AustralianStates;
use App\Service\FeatureFlagService;
use App\Service\Inventory\InventoryLedger;
use App\Service\Services\BookingWindows;
use App\Service\Services\ServiceBookingService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use InvalidArgumentException;

/**
 * Customer installation and repair bookings.
 */
class ServicesController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->viewBuilder()->addHelper('Money');
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function book(): ?Response
    {
        $flags = new FeatureFlagService();
        if (!$flags->enabled(FeatureFlagService::INSTALLATION_REPAIRS, false)) {
            $this->Flash->error(__('Installation and repair bookings are not open yet.'));
            $this->set([
                'serviceTypes' => [],
                'states' => AustralianStates::options(),
                'windows' => BookingWindows::options(),
                'bookingsOpen' => false,
            ]);

            return null;
        }

        $customer = $this->requireCustomer();
        $types = $this->fetchTable('ServiceTypes')->find()
            ->where(['is_active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        if ($this->request->is('post')) {
            try {
                $request = (new ServiceBookingService(new InventoryLedger()))->create(
                    $customer,
                    $this->request->getData(),
                );
                $this->Flash->success(__(
                    'Request {0} has been sent. We will confirm a time shortly.',
                    $request->request_number,
                ));

                return $this->redirect('/account/bookings');
            } catch (InvalidArgumentException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }

        $this->set([
            'serviceTypes' => $types,
            'states' => AustralianStates::options(),
            'windows' => BookingWindows::options(),
            'bookingsOpen' => true,
            'customer' => $customer,
        ]);

        return null;
    }

    /**
     * @return \App\Model\Entity\Customer
     */
    private function requireCustomer(): Customer
    {
        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            throw new NotFoundException();
        }
        $customer = $this->fetchTable('Customers')->find()
            ->where(['user_id' => (int)$identity->getIdentifier()])
            ->first();
        if ($customer === null) {
            throw new NotFoundException();
        }

        return $customer;
    }
}
